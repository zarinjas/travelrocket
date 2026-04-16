<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    @php
        $companyName = $invoice->tenant?->company_name ?: ($invoice->tenant?->name ?? config('app.name'));
        $companyPhone = $invoice->tenant?->company_phone;
        $companyEmail = $invoice->tenant?->company_email;
        $companyWebsite = $invoice->tenant?->company_website;
        $socialLinks = collect($invoice->tenant?->social_links ?? [])->filter();
    @endphp

    <p>Hello {{ $invoice->leadCustomer?->name ?? $invoice->leadCustomer?->full_name ?? 'Customer' }},</p>

    @if ($stage === 'overdue')
        <p>This is a reminder that your invoice is now overdue. Please arrange payment at your earliest convenience.</p>
    @elseif ($stage === 'due_today')
        <p>Your invoice is due today. Kindly complete the payment to avoid overdue status.</p>
    @elseif ($stage === 'due_soon')
        <p>Your invoice is due soon. Please prepare payment to keep your booking on track.</p>
    @else
        <p>This is a reminder for your invoice payment. Please review the details below.</p>
    @endif

    <p>
        <strong>Invoice Number:</strong> {{ $invoice->invoice_number }}<br>
        <strong>Package:</strong> {{ $invoice->booking?->package?->name }}<br>
        <strong>Total Amount:</strong> RM{{ number_format((float) $invoice->total_amount, 2) }}<br>
        <strong>Amount Paid:</strong> RM{{ number_format((float) $invoice->amount_paid, 2) }}<br>
        <strong>Balance Due:</strong> RM{{ number_format((float) $invoice->balance_due, 2) }}<br>
        <strong>Due Date:</strong> {{ optional($invoice->due_date)->format('Y-m-d') }}
    </p>

    <p>If payment has already been made, please ignore this email.</p>

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
