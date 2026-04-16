<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class CashflowController extends Controller
{
    public function index(Request $request, Tenant $tenant)
    {
        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->with(['customer', 'reminderLogs'])
            ->get();

        $insights = [
            'aging' => $this->buildAging($invoices),
            'cashflow_forecast' => $this->buildCashflowForecast($invoices),
            'collection_radar' => $this->buildCollectionRadar($invoices),
            'collection_actions' => $this->buildCollectionActions($invoices),
            'collector_options' => $invoices->pluck('customer.name')->unique()->filter()->values(),
            'outstanding_total' => $invoices->sum(fn($i) => $i->total - $i->paid_amount),
            'summary' => [
                'overdue_amount' => $invoices->where('status', 'overdue')->sum(fn($i) => $i->total - $i->paid_amount),
                'due_soon_amount' => $invoices->filter(fn($i) => 
                    $i->due_date && 
                    Carbon::parse($i->due_date)->isFuture() && 
                    Carbon::parse($i->due_date)->diffInDays(now()) <= 7
                )->sum(fn($i) => $i->total - $i->paid_amount),
                'at_risk_accounts' => $invoices->filter(fn($i) => ($i->total - $i->paid_amount) > 0 && Carbon::parse($i->due_date)->diffInDays(now(), false) > 15)->count(),
            ]
        ];

        return Inertia::render('Workspace/Cashflow/CommandCenterPage', [
            'workspace' => $tenant,
            'invoices' => $invoices,
            'financialInsights' => $insights,
        ]);
    }

    private function buildAging($invoices)
    {
        $now = now();
        $buckets = [
            'current' => ['label' => 'Current (Not Due)', 'amount' => 0, 'count' => 0],
            '1_7' => ['label' => '1-7 Days Overdue', 'amount' => 0, 'count' => 0],
            '8_14' => ['label' => '8-14 Days Overdue', 'amount' => 0, 'count' => 0],
            '15_30' => ['label' => '15-30 Days Overdue', 'amount' => 0, 'count' => 0],
            '31_60' => ['label' => '31-60 Days Overdue', 'amount' => 0, 'count' => 0],
            '61_plus' => ['label' => '60+ Days Overdue', 'amount' => 0, 'count' => 0],
        ];

        foreach ($invoices as $inv) {
            $balance = $inv->total - $inv->paid_amount;
            if ($balance <= 0) continue;

            $dueDate = Carbon::parse($inv->due_date);
            if ($dueDate->isFuture()) {
                $buckets['current']['amount'] += $balance;
                $buckets['current']['count']++;
                continue;
            }

            $diff = $dueDate->diffInDays($now);
            if ($diff <= 7) { $buckets['1_7']['amount'] += $balance; $buckets['1_7']['count']++; }
            elseif ($diff <= 14) { $buckets['8_14']['amount'] += $balance; $buckets['8_14']['count']++; }
            elseif ($diff <= 30) { $buckets['15_30']['amount'] += $balance; $buckets['15_30']['count']++; }
            elseif ($diff <= 60) { $buckets['31_60']['amount'] += $balance; $buckets['31_60']['count']++; }
            else { $buckets['61_plus']['amount'] += $balance; $buckets['61_plus']['count']++; }
        }

        return $buckets;
    }

    private function buildCashflowForecast($invoices)
    {
        $now = now();
        return [
            [
                'label' => 'Next 7 Days',
                'days' => 7,
                'expected_collection' => $invoices->filter(fn($i) => ($i->total - $i->paid_amount) > 0 && Carbon::parse($i->due_date)->between($now, $now->copy()->addDays(7)))->sum(fn($i) => $i->total - $i->paid_amount),
                'invoice_count' => $invoices->filter(fn($i) => ($i->total - $i->paid_amount) > 0 && Carbon::parse($i->due_date)->between($now, $now->copy()->addDays(7)))->count(),
                'at_risk_amount' => 0
            ],
            [
                'label' => 'Next 14 Days',
                'days' => 14,
                'expected_collection' => $invoices->filter(fn($i) => ($i->total - $i->paid_amount) > 0 && Carbon::parse($i->due_date)->between($now, $now->copy()->addDays(14)))->sum(fn($i) => $i->total - $i->paid_amount),
                'invoice_count' => $invoices->filter(fn($i) => ($i->total - $i->paid_amount) > 0 && Carbon::parse($i->due_date)->between($now, $now->copy()->addDays(14)))->count(),
                'at_risk_amount' => 0
            ],
            [
                'label' => 'Next 30 Days',
                'days' => 30,
                'expected_collection' => $invoices->filter(fn($i) => ($i->total - $i->paid_amount) > 0 && Carbon::parse($i->due_date)->between($now, $now->copy()->addDays(30)))->sum(fn($i) => $i->total - $i->paid_amount),
                'invoice_count' => $invoices->filter(fn($i) => ($i->total - $i->paid_amount) > 0 && Carbon::parse($i->due_date)->between($now, $now->copy()->addDays(30)))->count(),
                'at_risk_amount' => 0
            ]
        ];
    }

    private function buildCollectionRadar($invoices)
    {
        return $invoices->filter(fn($i) => ($i->total - $i->paid_amount) > 0)
            ->map(fn($i) => [
                'id' => $i->id,
                'customer_name' => $i->customer->name ?? 'Unknown',
                'invoice_number' => $i->invoice_number,
                'balance_due' => $i->total - $i->paid_amount,
                'days_overdue' => Carbon::parse($i->due_date)->isPast() ? Carbon::parse($i->due_date)->diffInDays(now()) : 0,
                'urgency' => Carbon::parse($i->due_date)->diffInDays(now(), false) > 30 ? 'critical' : (Carbon::parse($i->due_date)->isPast() ? 'high' : 'medium')
            ])
            ->sortByDesc('days_overdue')
            ->values()
            ->all();
    }

    private function buildCollectionActions($invoices)
    {
        $now = now();
        return $invoices->filter(fn($i) => ($i->total - $i->paid_amount) > 0)
            ->map(function($i) use ($now) {
                $dueDate = Carbon::parse($i->due_date);
                $daysOverdue = $dueDate->isPast() ? $dueDate->diffInDays($now) : 0;
                
                $stage = 'general';
                if ($dueDate->isToday()) $stage = 'due_today';
                elseif ($dueDate->isPast()) $stage = 'overdue';
                elseif ($dueDate->diffInDays($now) <= 7) $stage = 'due_soon';

                $action = "Follow up with customer";
                if ($daysOverdue > 30) $action = "Urgent: Final notice required / Phone call";
                elseif ($daysOverdue > 14) $action = "Second reminder: WhatsApp & Email";
                elseif ($daysOverdue > 0) $action = "First reminder: Friendly follow-up";
                elseif ($dueDate->isToday()) $action = "Payment expected today";
                
                return [
                    'id' => $i->id,
                    'customer_name' => $i->customer->name ?? 'Unknown',
                    'invoice_number' => $i->invoice_number,
                    'balance_due' => $i->total - $i->paid_amount,
                    'days_overdue' => $daysOverdue,
                    'stage' => $stage,
                    'recommended_action' => $action,
                    'collector_name' => $i->customer->name ?? 'Unknown',
                ];
            })
            ->sortByDesc('days_overdue')
            ->values()
            ->all();
    }

    public function markCollectionAction(Request $request, Tenant $tenant, Invoice $invoice)
    {
        $invoice->reminderLogs()->create([
            'tenant_id' => $tenant->id,
            'action_type' => 'manual_mark_done',
            'notes' => 'Action marked as done from Command Center. Stage: ' . $request->stage,
            'status' => 'completed',
            'performed_at' => now(),
        ]);

        return back()->with('success', 'Action marked as done.');
    }

    public function financialExport(Tenant $tenant)
    {
        $invoices = Invoice::where('tenant_id', $tenant->id)->with('customer')->get();
        $csvHeader = ['Invoice #', 'Customer', 'Date', 'Due Date', 'Total (RM)', 'Paid (RM)', 'Balance (RM)', 'Status'];
        
        $callback = function() use ($invoices, $csvHeader) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvHeader);
            foreach ($invoices as $inv) {
                fputcsv($file, [
                    $inv->invoice_number,
                    $inv->customer->name ?? 'Unknown',
                    $inv->issued_date,
                    $inv->due_date,
                    number_format($inv->total, 2),
                    number_format($inv->paid_amount, 2),
                    number_format($inv->total - $inv->paid_amount, 2),
                    $inv->status
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="financial-report-'.now()->format('Y-md').'.csv"',
        ]);
    }

    public function collectionActionsExport(Tenant $tenant)
    {
        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->whereRaw('total > paid_amount')
            ->with('customer')
            ->get();
            
        $csvHeader = ['Invoice #', 'Customer', 'Balance Due (RM)', 'Days Overdue', 'Action Required'];
        
        $callback = function() use ($invoices, $csvHeader) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvHeader);
            foreach ($invoices as $inv) {
                $dueDate = Carbon::parse($inv->due_date);
                $days = $dueDate->isPast() ? $dueDate->diffInDays(now()) : 0;
                $action = $days > 14 ? 'Final Notice' : ($days > 0 ? 'Friendly Reminder' : 'Upcoming Follow-up');
                
                fputcsv($file, [
                    $inv->invoice_number,
                    $inv->customer->name ?? 'Unknown',
                    number_format($inv->total - $inv->paid_amount, 2),
                    $days,
                    $action
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="collection-queue-'.now()->format('Y-md').'.csv"',
        ]);
    }
}
