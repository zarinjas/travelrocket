@php
    $companyName = $quotation->tenant?->company_name ?: ($quotation->tenant?->name ?? config('app.name'));
    $companySubName = $quotation->tenant?->name ?? $companyName;
    $companyAddressBlock = trim(implode("\n", array_filter([
        $quotation->tenant?->company_address,
        $quotation->tenant?->company_phone,
        $quotation->tenant?->company_email,
    ]))) ?: '-';

    $billToName = $quotation->leadCustomer?->name ?? $quotation->leadCustomer?->full_name ?? '-';
    $subject = $quotation->package?->name ?? 'Travel Package';

    $itemLines = array_filter([
        $quotation->package?->category,
        $quotation->package?->destination,
        optional($quotation->package?->start_date)->format('d M Y').' - '.optional($quotation->package?->end_date)->format('d M Y'),
        $quotation->remarks,
    ]);

    $defaultTerms = "50% payment is required upon confirmation.\nBalance to be paid before full delivery of services.";

    $paymentTerms = trim((string) ($quotation->tenant?->quotation_terms ?? '')) ?: $defaultTerms;

    $paymentMethod = trim(implode("\n", array_filter([
        'Cheque or direct bank deposit. Please notify us if you made direct bank deposits. Payments can be made to:',
        strtoupper((string) ($quotation->tenant?->company_name ?? $quotation->tenant?->name ?? config('app.name'))),
        strtoupper((string) ($quotation->tenant?->bank_name ?? '-')),
        'A/C NO.: '.((string) ($quotation->tenant?->bank_account_number ?? '-')),
    ])));

    $items = [[
        'no' => 1,
        'title' => $quotation->package?->name ?? 'Travel Service',
        'lines' => $itemLines,
        'qty' => '1.00',
        'rate' => number_format((float) $quotation->subtotal, 2),
        'amount' => number_format((float) $quotation->subtotal, 2),
    ]];
@endphp

@include('pdfs.partials.document-master', [
    'documentLabel' => 'QUOTATION',
    'documentNumber' => $quotation->quotation_number,
    'logoDataUri' => $logoDataUri ?? null,
    'companyName' => $companyName,
    'companySubName' => $companySubName,
    'companyAddressBlock' => $companyAddressBlock,
    'billToName' => $billToName,
    'subject' => $subject,
    'dateLabel' => 'Quotation Date',
    'dateValue' => optional($quotation->created_at ?? now())->format('d M Y'),
    'items' => $items,
    'subTotalValue' => number_format((float) $quotation->subtotal, 2),
    'totalValue' => 'MYR'.number_format((float) $quotation->total_amount, 2),
    'paymentTerms' => $paymentTerms,
    'paymentMethod' => $paymentMethod,
])
