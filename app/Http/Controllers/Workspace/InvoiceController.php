<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Quotation;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Tenant $tenant): Response
    {
        $invoices = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->with('customer:id,name,email')
            ->latest()
            ->get();

        return Inertia::render('Workspace/Invoices/IndexPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'invoices' => $invoices,
        ]);
    }

    public function create(Tenant $tenant): Response
    {
        return Inertia::render('Workspace/Invoices/FormPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'customers' => Customer::all(['id', 'name', 'email', 'phone']),
            'packages' => Package::all(['id', 'name', 'price', 'type', 'available_seats']),
            'nextId' => Invoice::generatePublicId(),
            'fromQuote' => null,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'quote_id' => ['nullable', 'exists:quotations,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'manual_customer_data' => ['nullable', 'array'],
            'subject' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.package_id' => ['nullable', 'exists:packages,id'],
            'items.*.description' => ['required', 'string'],
            'items.*.qty' => ['required', 'numeric', 'min:1'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'sub_total' => ['required', 'numeric'],
            'total' => ['required', 'numeric'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $tenant) {
            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'public_id' => Invoice::generatePublicId(),
                ...$validated,
                'status' => 'Unpaid',
            ]);

            // Deduct seats if package_id exists in items
            foreach ($validated['items'] as $item) {
                if (!empty($item['package_id'])) {
                    $package = Package::find($item['package_id']);
                    if ($package && $package->available_seats >= $item['qty']) {
                        $package->decrement('available_seats', $item['qty']);
                    }
                }
            }

            if ($request->filled('quote_id')) {
                Quotation::where('id', $request->quote_id)->update(['status' => 'Closed']);
            }

            return redirect()
                ->route('invoices.show', ['tenant' => $tenant, 'invoice' => $invoice])
                ->with('success', 'Invoice created successfully and seats updated.');
        });
    }

    public function show(Tenant $tenant, Invoice $invoice): Response
    {
        $invoice->load(['customer', 'quotation.package', 'booking.package']);

        $package = $invoice->booking?->package ?? $invoice->quotation?->package;
        $items = [];
        if ($package) {
            $items[] = [
                'description' => $package->name,
                'qty' => 1,
                'rate' => $package->price,
                'amount' => $package->price,
            ];
        }

        $quotationData = null;
        if ($invoice->quotation) {
            $quotationData = array_merge($invoice->quotation->toArray(), [
                'public_id' => $invoice->quotation->quotation_number,
            ]);
        }

        return Inertia::render('Workspace/Invoices/ShowPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'invoice' => array_merge($invoice->toArray(), [
                'public_id' => $invoice->invoice_number,
                'sub_total' => $invoice->subtotal,
                'total' => $invoice->total_amount,
                'paid_amount' => $invoice->amount_paid,
                'notes' => $invoice->remarks,
                'subject' => $package?->name,
                'items' => $items,
                'quotation' => $quotationData,
            ]),
        ]);
    }

    public function recordPayment(Request $request, Tenant $tenant, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string'],
            'payment_date' => ['required', 'date'],
            'proof' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($request, $tenant, $invoice, $validated) {
            $prevPaid = $invoice->amount_paid ?? 0;
            $newPaidTotal = $prevPaid + $validated['amount'];
            
            $status = 'Partially Paid';
            if ($newPaidTotal >= $invoice->total_amount) {
                $status = 'Fully Paid';
                $newPaidTotal = $invoice->total_amount; // Cap at total
            }

            $editData = [
                'amount_paid' => $newPaidTotal,
                'status' => $status,
                'payment_details' => array_merge($invoice->payment_details ?? [], [
                    [
                        'amount' => $validated['amount'],
                        'method' => $validated['payment_method'],
                        'date' => $validated['payment_date'],
                        'notes' => $validated['notes'],
                        'logged_at' => now()->toDateTimeString(),
                    ]
                ]),
            ];

            if ($request->hasFile('proof')) {
                $editData['payment_proof'] = $request->file('proof')->store('payments', 'public');
            }

            $invoice->update($editData);

            return redirect()
                ->route('invoices.show', ['tenant' => $tenant, 'invoice' => $invoice])
                ->with('success', 'Payment recorded successfully.');
        });
    }

    public function convertFromQuote(Request $request, Tenant $tenant, Quotation $quotation): Response
    {
        return Inertia::render('Workspace/Invoices/FormPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'customers' => Customer::all(['id', 'name', 'email', 'phone']),
            'packages' => Package::all(['id', 'name', 'price', 'type', 'available_seats']),
            'nextId' => Invoice::generatePublicId(),
            'fromQuote' => $quotation->toArray(),
        ]);
    }

    public function bulkDelete(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:invoices,id'],
        ]);

        Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $validated['ids'])
            ->delete();

        return redirect()
            ->route('invoices.index', ['tenant' => $tenant])
            ->with('success', count($validated['ids']) . ' invoice(s) deleted.');
    }

    public function exportCsv(Request $request, Tenant $tenant)
    {
        $query = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->with('customer:id,name,email');

        if ($request->has('ids')) {
            $query->whereIn('id', explode(',', $request->ids));
        }

        $invoices = $query->latest()->get();

        $csv = "ID,Customer,Email,Subject,Total,Paid,Status,Date\n";
        foreach ($invoices as $inv) {
            $name = str_replace('"', '""', $inv->customer?->name ?? $inv->manual_customer_data['name'] ?? 'Walk-in');
            $email = $inv->customer?->email ?? $inv->manual_customer_data['email'] ?? '';
            $subject = str_replace('"', '""', $inv->subject ?? '');
            $csv .= "\"{$inv->public_id}\",\"{$name}\",\"{$email}\",\"{$subject}\",{$inv->total},{$inv->paid_amount},{$inv->status},{$inv->created_at->format('Y-m-d')}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="invoices-' . now()->format('Y-m-d') . '.csv"');
    }

    public function generatePdf(Tenant $tenant, Invoice $invoice)
    {
        $invoice->load(['customer', 'quotation.package', 'booking.package']);

        $package = $invoice->booking?->package ?? $invoice->quotation?->package;
        $items = [];
        if ($package) {
            $items[] = [
                'description' => $package->name,
                'qty' => 1,
                'rate' => $package->price,
                'amount' => $package->price,
            ];
        }

        $data = [
            'workspace' => $tenant->only(['id', 'name', 'slug', 'logo_url']),
            'invoice' => array_merge($invoice->toArray(), [
                'public_id' => $invoice->invoice_number,
                'sub_total' => $invoice->subtotal,
                'total' => $invoice->total_amount,
                'paid_amount' => $invoice->amount_paid,
                'notes' => $invoice->remarks,
                'subject' => $package?->name,
                'items' => $items,
            ]),
        ];

        $pdf = \PDF::loadView('pdf.invoice', $data);
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }
}
