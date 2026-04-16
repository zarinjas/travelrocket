@php
    $companyName = $invoice->tenant?->company_name ?: ($invoice->tenant?->name ?? config('app.name'));
    $companySubName = $invoice->tenant?->name ?? $companyName;
    $companyAddressBlock = trim(implode("\n", array_filter([
        $invoice->tenant?->company_address,
        $invoice->tenant?->company_phone,
        $invoice->tenant?->company_email,
    ]))) ?: '-';

    $billToName = $invoice->leadCustomer?->name ?? $invoice->leadCustomer?->full_name ?? '-';
    $subjectPackage = $invoice->booking?->package?->name ?? $invoice->quotation?->package?->name;
    $subject = $subjectPackage ?? 'Travel Package';

    $itemPackage = $invoice->booking?->package ?: $invoice->quotation?->package;

    $rawRemarks = trim((string) ($invoice->remarks ?? ''));
    $paymentTermsFromRemarks = '';
    if ($rawRemarks !== '' && str_contains($rawRemarks, 'Payment Terms:')) {
        $paymentTermsFromRemarks = trim((string) \Illuminate\Support\Str::after($rawRemarks, 'Payment Terms:'));
    }

    $defaultTerms = "50% payment is required upon confirmation.\nBalance to be paid before full delivery of services.";

    $paymentTerms = $paymentTermsFromRemarks !== ''
        ? $paymentTermsFromRemarks
        : (trim((string) ($invoice->tenant?->quotation_terms ?? '')) ?: $defaultTerms);

    $itemLines = array_filter([
        $itemPackage?->category,
        $itemPackage?->destination,
        optional($itemPackage?->start_date)->format('d M Y').' - '.optional($itemPackage?->end_date)->format('d M Y'),
        $rawRemarks,
    ]);

    $paymentMethod = trim(implode("\n", array_filter([
        'Cheque or direct bank deposit. Please notify us if you made direct bank deposits. Payments can be made to:',
        strtoupper((string) ($invoice->tenant?->company_name ?? $invoice->tenant?->name ?? config('app.name'))),
        strtoupper((string) ($invoice->tenant?->bank_name ?? '-')),
        'A/C NO.: '.((string) ($invoice->tenant?->bank_account_number ?? '-')),
    ])));

    $items = [[
        'no' => 1,
        'title' => $itemPackage?->name ?? 'Travel Service',
        'lines' => $itemLines,
        'qty' => '1.00',
        'rate' => number_format((float) $invoice->subtotal, 2),
        'amount' => number_format((float) $invoice->subtotal, 2),
    ]];
@endphp

@include('pdfs.partials.document-master', [
    'documentLabel' => 'INVOICE',
    'documentNumber' => $invoice->invoice_number,
    'logoDataUri' => $logoDataUri ?? null,
    'companyName' => $companyName,
    'companySubName' => $companySubName,
    'companyAddressBlock' => $companyAddressBlock,
    'billToName' => $billToName,
    'subject' => $subject,
    'dateLabel' => 'Invoice Date',
    'dateValue' => optional($invoice->issued_date ?? now())->format('d M Y'),
    'items' => $items,
    'subTotalValue' => number_format((float) $invoice->subtotal, 2),
    'totalValue' => 'MYR'.number_format((float) $invoice->total_amount, 2),
    'paymentTerms' => $paymentTerms,
    'paymentMethod' => $paymentMethod,
])
