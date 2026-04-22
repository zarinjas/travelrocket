<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Quotation;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuotationController extends Controller
{
    public function index(Tenant $tenant): Response
    {
        $quotations = Quotation::query()
            ->where('tenant_id', $tenant->id)
            ->with('customer:id,name,email')
            ->latest()
            ->get();

        return Inertia::render('Workspace/Quotations/IndexPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'quotations' => $quotations,
        ]);
    }

    public function create(Tenant $tenant): Response
    {
        return Inertia::render('Workspace/Quotations/FormPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'customers' => Customer::all(['id', 'name', 'email', 'phone']),
            'packages' => Package::all(['id', 'name', 'price', 'type']),
            'nextId' => Quotation::generatePublicId(),
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'manual_customer_data' => ['nullable', 'array'],
            'subject' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.qty' => ['required', 'numeric', 'min:1'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'sub_total' => ['required', 'numeric'],
            'total' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'expiry_date' => ['required', 'date'],
        ]);

        $quotation = Quotation::create([
            'tenant_id' => $tenant->id,
            'public_id' => Quotation::generatePublicId(),
            ...$validated,
            'status' => 'Draft',
        ]);

        return redirect()
            ->route('quotations.show', ['tenant' => $tenant, 'quotation' => $quotation])
            ->with('success', 'Quotation created successfully.');
    }

    public function show(Tenant $tenant, Quotation $quotation): Response
    {
        $quotation->load(['customer', 'package']);

        $items = [];
        if ($quotation->package) {
            $items[] = [
                'description' => $quotation->package->name,
                'qty' => 1,
                'rate' => $quotation->package->price,
                'amount' => $quotation->package->price,
            ];
        }

        return Inertia::render('Workspace/Quotations/ShowPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'quotation' => array_merge($quotation->toArray(), [
                'public_id' => $quotation->quotation_number,
                'sub_total' => $quotation->subtotal,
                'total' => $quotation->total_amount,
                'notes' => $quotation->remarks,
                'expiry_date' => $quotation->valid_until,
                'subject' => $quotation->package?->name,
                'items' => $items,
            ]),
        ]);
    }

    public function bulkDelete(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:quotations,id'],
        ]);

        Quotation::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $validated['ids'])
            ->delete();

        return redirect()
            ->route('quotations.index', ['tenant' => $tenant])
            ->with('success', count($validated['ids']) . ' quotation(s) deleted.');
    }

    public function exportCsv(Request $request, Tenant $tenant)
    {
        $query = Quotation::query()
            ->where('tenant_id', $tenant->id)
            ->with('customer:id,name,email');

        if ($request->has('ids')) {
            $query->whereIn('id', explode(',', $request->ids));
        }

        $quotations = $query->latest()->get();

        $csv = "ID,Customer,Email,Subject,Amount,Status,Date\n";
        foreach ($quotations as $q) {
            $name = str_replace('"', '""', $q->customer?->name ?? $q->manual_customer_data['name'] ?? 'Walk-in');
            $email = $q->customer?->email ?? $q->manual_customer_data['email'] ?? '';
            $subject = str_replace('"', '""', $q->subject ?? '');
            $csv .= "\"{$q->public_id}\",\"{$name}\",\"{$email}\",\"{$subject}\",{$q->total},{$q->status},{$q->created_at->format('Y-m-d')}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="quotations-' . now()->format('Y-m-d') . '.csv"');
    }

    public function generatePdf(Tenant $tenant, Quotation $quotation)
    {
        $quotation->load(['customer']);

        $items = [];
        if ($quotation->package) {
            $items[] = [
                'description' => $quotation->package->name,
                'qty' => 1,
                'rate' => $quotation->package->price,
                'amount' => $quotation->package->price,
            ];
        }

        $data = [
            'workspace' => $tenant->only(['id', 'name', 'slug', 'logo_url']),
            'quotation' => array_merge($quotation->toArray(), [
                'public_id' => $quotation->quotation_number,
                'sub_total' => $quotation->subtotal,
                'total' => $quotation->total_amount,
                'notes' => $quotation->remarks,
                'expiry_date' => $quotation->valid_until,
                'subject' => $quotation->package?->name,
                'items' => $items,
            ]),
        ];

        $pdf = \PDF::loadView('pdf.quotation', $data);
        return $pdf->download('quotation-' . $quotation->quotation_number . '.pdf');
    }
}
