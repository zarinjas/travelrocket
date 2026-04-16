<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quotation Invoice</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    @php
        $companyName = $quotation->tenant?->company_name ?: ($quotation->tenant?->name ?? config('app.name'));
        $companyPhone = $quotation->tenant?->company_phone;
        $companyEmail = $quotation->tenant?->company_email;
        $companyWebsite = $quotation->tenant?->company_website;
        $socialLinks = collect($quotation->tenant?->social_links ?? [])->filter();
    @endphp

    <p>Hello {{ $quotation->leadCustomer?->name ?? 'Customer' }},</p>

    <p>Thank you for your interest in our travel package. Please find your quotation invoice attached as a PDF.</p>

    <p><strong>Quotation Number:</strong> {{ $quotation->quotation_number }}<br>
    <strong>Package:</strong> {{ $quotation->package?->name }}<br>
    <strong>Total Amount:</strong> RM{{ number_format((float) $quotation->total_amount, 2) }}<br>
    <strong>Valid Until:</strong> {{ optional($quotation->valid_until)->format('Y-m-d') }}</p>

    <p>If you would like to proceed, kindly reply to this email and our team will assist with booking confirmation.</p>

    <p>Best regards,<br>
    {{ $companyName }}</p>

    <p style="font-size: 12px; color: #6b7280;">
        {{ $companyPhone ?: '-' }}
        @if($companyEmail)
            · {{ $companyEmail }}
        @endif
        @if($companyWebsite)
            · {{ $companyWebsite }}
        @endif
    </p>

    @if($socialLinks->isNotEmpty())
        <p style="font-size: 12px; color: #6b7280;">Social: {{ $socialLinks->keys()->implode(' · ') }}</p>
    @endif
</body>
</html>
