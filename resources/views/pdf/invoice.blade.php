<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @font-face {
            font-family: 'Poppins';
            font-weight: 400;
            font-style: normal;
            src: url("{{ public_path('fonts/poppins/Poppins-Regular.woff') }}") format('woff');
        }
        @font-face {
            font-family: 'Poppins';
            font-weight: 500;
            font-style: normal;
            src: url("{{ public_path('fonts/poppins/Poppins-Medium.woff') }}") format('woff');
        }
        @font-face {
            font-family: 'Poppins';
            font-weight: 600;
            font-style: normal;
            src: url("{{ public_path('fonts/poppins/Poppins-SemiBold.woff') }}") format('woff');
        }
        @font-face {
            font-family: 'Poppins';
            font-weight: 700;
            font-style: normal;
            src: url("{{ public_path('fonts/poppins/Poppins-Bold.woff') }}") format('woff');
        }

        @page { margin: 0; size: A4; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', DejaVu Sans, Arial, sans-serif;
            color: #111827;
            font-size: 9.5px;
            line-height: 1.5;
            background: #ffffff;
        }

        /* ───────────────────────────────────────
           HEADER
        ─────────────────────────────────────── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-left {
            background: linear-gradient(135deg, #0f2442 0%, #1e3d72 100%);
            width: 55%;
            padding: 36px 24px 36px 44px;
            vertical-align: top;
        }
        .header-right {
            background: linear-gradient(135deg, #0f2442 0%, #1e3d72 100%);
            width: 45%;
            padding: 36px 44px 36px 20px;
            vertical-align: middle;
            text-align: right;
            border-left: 1px solid rgba(255,255,255,0.07);
        }

        .brand-logo {
            height: 48px;
            width: auto;
            max-width: 160px;
            margin-bottom: 16px;
            display: block;
        }
        .brand-name {
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 2px;
        }
        .brand-reg {
            font-size: 8px;
            color: #93c5fd;
            margin-bottom: 10px;
        }
        .brand-address {
            font-size: 8.5px;
            color: rgba(255,255,255,0.75);
            line-height: 1.8;
        }
        .brand-contact {
            font-size: 8.5px;
            color: rgba(255,255,255,0.6);
            margin-top: 6px;
            line-height: 1.8;
        }

        .doc-type {
            font-size: 32px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -1px;
            line-height: 1;
            margin-bottom: 16px;
        }
        .doc-meta-table {
            width: auto;
            margin-left: auto;
            border-collapse: collapse;
        }
        .doc-meta-table td { padding: 3px 0; font-size: 9.5px; }
        .meta-key { color: rgba(255,255,255,0.55); padding-right: 12px; text-align: right; white-space: nowrap; }
        .meta-val { color: #ffffff; font-weight: 600; text-align: right; }

        .status-badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 600;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        .status-unpaid  { background: #fef3c7; color: #78350f; }
        .status-partial { background: #dbeafe; color: #1e3a8a; }
        .status-paid    { background: #dcfce7; color: #14532d; }

        /* ───────────────────────────────────────
           CONTENT WRAPPER
        ─────────────────────────────────────── */
        .content { padding: 28px 44px 40px; }

        /* ───────────────────────────────────────
           BILL TO + SUBJECT
        ─────────────────────────────────────── */
        .info-wrap {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .info-labels-row { width: 100%; border-collapse: collapse; }
        .info-labels-row td {
            background: #f3f4f6;
            padding: 5px 16px;
            font-size: 7.5px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-labels-row td + td { border-left: 1px solid #e5e7eb; }
        .info-values-row { width: 100%; border-collapse: collapse; }
        .info-values-row td { padding: 12px 16px; vertical-align: top; }
        .info-values-row td + td { border-left: 1px solid #e5e7eb; }
        .client-name { font-size: 12px; font-weight: 600; color: #111827; margin-bottom: 3px; }
        .client-sub  { font-size: 9px; color: #6b7280; line-height: 1.8; }
        .subject-text { font-size: 9.5px; font-weight: 500; color: #1f2937; }

        /* ───────────────────────────────────────
           ITEMS TABLE
        ─────────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .items-table thead tr {
            background: linear-gradient(135deg, #0f2442 0%, #1e3d72 100%);
        }
        .items-table thead th {
            padding: 9px 12px;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: rgba(255,255,255,0.8);
            text-align: left;
        }
        .items-table thead th.right  { text-align: right; }
        .items-table thead th.center { text-align: center; }

        .items-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        .items-table tbody tr:nth-child(even) { background: #fafafa; }

        .items-table tbody td {
            padding: 10px 12px;
            font-size: 9.5px;
            vertical-align: top;
            color: #111827;
        }
        .items-table tbody td.right  { text-align: right; }
        .items-table tbody td.center { text-align: center; color: #6b7280; }

        .item-name { font-weight: 600; color: #111827; margin-bottom: 3px; }
        .item-desc { font-size: 8.5px; color: #6b7280; line-height: 1.6; white-space: pre-wrap; }

        .col-no   { width: 5%; }
        .col-desc { width: 49%; }
        .col-qty  { width: 8%; }
        .col-rate { width: 19%; }
        .col-amt  { width: 19%; font-weight: 600; }

        /* ───────────────────────────────────────
           BOTTOM: NOTES LEFT | TOTALS RIGHT
        ─────────────────────────────────────── */
        .bottom-table  { width: 100%; border-collapse: collapse; }
        .col-notes  { width: 56%; padding-right: 32px; vertical-align: top; }
        .col-totals { width: 44%; vertical-align: top; }

        .section-label {
            font-size: 7.5px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #9ca3af;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .section-text {
            font-size: 9px;
            color: #374151;
            line-height: 1.7;
            white-space: pre-wrap;
        }
        .section-block { margin-bottom: 16px; }
        .section-block:last-child { margin-bottom: 0; }

        /* Bank rows */
        .bank-row { display: flex; padding: 2.5px 0; font-size: 9px; }
        .bank-k   { color: #9ca3af; min-width: 112px; }
        .bank-v   { font-weight: 500; color: #1f2937; }

        /* Totals */
        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 9.5px;
            border-bottom: 1px solid #f3f4f6;
        }
        .total-line:last-of-type { border-bottom: none; }
        .total-k { color: #6b7280; }
        .total-v { font-weight: 600; color: #111827; }
        .total-v.green { color: #15803d; }

        .grand-total {
            background: linear-gradient(135deg, #0f2442 0%, #1e3d72 100%);
            padding: 13px 16px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }
        .grand-total-label { font-size: 9px; font-weight: 600; color: rgba(255,255,255,0.7); letter-spacing: 1px; text-transform: uppercase; }
        .grand-total-value { font-size: 20px; font-weight: 700; color: #ffffff; }

        .balance-row {
            background: #fff7ed;
            border: 1px solid #fdba74;
            padding: 9px 16px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
        }
        .balance-label { font-size: 8.5px; font-weight: 600; color: #9a3412; text-transform: uppercase; letter-spacing: 0.5px; }
        .balance-value { font-size: 14px; font-weight: 700; color: #9a3412; }

        /* ───────────────────────────────────────
           FOOTER
        ─────────────────────────────────────── */
        .footer {
            padding: 11px 44px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            font-size: 7.5px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>

{{-- ── HEADER ── --}}
<table class="header-table">
    <tr>
        <td class="header-left">
            @if(!empty($workspace['logo_url']))
                <img src="{{ $workspace['logo_url'] }}" alt="Logo" class="brand-logo">
            @endif
            <div class="brand-name">{{ $workspace['company_name'] ?: ($workspace['name'] ?? 'Company Name') }}</div>
            @if(!empty($workspace['company_reg']))
                <div class="brand-reg">Reg. No: {{ $workspace['company_reg'] }}</div>
            @endif
            @if(!empty($workspace['company_address']))
                <div class="brand-address">{{ $workspace['company_address'] }}</div>
            @endif
            @if(!empty($workspace['company_phone']) || !empty($workspace['company_email']))
                <div class="brand-contact">
                    @if(!empty($workspace['company_phone'])){{ $workspace['company_phone'] }}<br>@endif
                    @if(!empty($workspace['company_email'])){{ $workspace['company_email'] }}@endif
                </div>
            @endif
        </td>
        <td class="header-right">
            <div class="doc-type">INVOICE</div>
            <table class="doc-meta-table">
                <tr>
                    <td class="meta-key">Invoice&nbsp;#</td>
                    <td class="meta-val">{{ $invoice['public_id'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="meta-key">Date</td>
                    <td class="meta-val">{{ !empty($invoice['created_at']) ? \Carbon\Carbon::parse($invoice['created_at'])->format('d M Y') : '—' }}</td>
                </tr>
                @if(!empty($invoice['due_date']))
                <tr>
                    <td class="meta-key">Due&nbsp;Date</td>
                    <td class="meta-val">{{ \Carbon\Carbon::parse($invoice['due_date'])->format('d M Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td class="meta-key">Status</td>
                    <td class="meta-val">
                        @php
                            $status = $invoice['status'] ?? 'Unpaid';
                            $cls = match(true) {
                                str_contains($status, 'Fully')   => 'status-paid',
                                str_contains($status, 'Partial') => 'status-partial',
                                default                          => 'status-unpaid',
                            };
                        @endphp
                        <span class="status-badge {{ $cls }}">{{ $status }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="content">

    {{-- ── BILL TO + SUBJECT ── --}}
    <div class="info-wrap">
        <table class="info-labels-row">
            <tr>
                <td style="width:55%">Bill To</td>
                <td style="width:45%">Subject</td>
            </tr>
        </table>
        <table class="info-values-row">
            <tr>
                <td style="width:55%">
                    @if(!empty($invoice['customer']))
                        <div class="client-name">{{ $invoice['customer']['name'] ?? '—' }}</div>
                        <div class="client-sub">
                            @if(!empty($invoice['customer']['phone'])){{ $invoice['customer']['phone'] }}<br>@endif
                            {{ $invoice['customer']['email'] ?? '' }}
                        </div>
                    @else
                        <div class="client-name">{{ ($invoice['manual_customer_data']['name'] ?? null) ?: 'Walk-in Customer' }}</div>
                        <div class="client-sub">
                            @if(!empty($invoice['manual_customer_data']['phone'])){{ $invoice['manual_customer_data']['phone'] }}<br>@endif
                            {{ $invoice['manual_customer_data']['email'] ?? '' }}
                        </div>
                    @endif
                </td>
                <td style="width:45%">
                    @if(!empty($invoice['subject']))
                        <div class="subject-text">{{ $invoice['subject'] }}</div>
                    @else
                        <div class="client-sub">—</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- ── LINE ITEMS ── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-no center">#</th>
                <th class="col-desc">Item &amp; Description</th>
                <th class="col-qty center">Qty</th>
                <th class="col-rate right">Rate</th>
                <th class="col-amt right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($invoice['items'] ?? []) as $i => $item)
                @php $lines = explode("\n", $item['description'] ?? ''); @endphp
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>
                        <div class="item-name">{{ $lines[0] ?? '—' }}</div>
                        @if(count($lines) > 1)
                            <div class="item-desc">{{ implode("\n", array_slice($lines, 1)) }}</div>
                        @endif
                    </td>
                    <td class="center">{{ $item['qty'] ?? 1 }}</td>
                    <td class="right">RM {{ number_format((float)($item['rate'] ?? 0), 2) }}</td>
                    <td class="right col-amt">RM {{ number_format((float)($item['amount'] ?? ($item['qty'] * $item['rate'])), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#9ca3af; padding:20px;">No items added.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ── BOTTOM: NOTES LEFT | TOTALS RIGHT ── --}}
    <table class="bottom-table">
        <tr>
            <td class="col-notes">
                @if(!empty($invoice['notes']))
                    <div class="section-block">
                        <div class="section-label">Payment Instructions</div>
                        <div class="section-text">{{ $invoice['notes'] }}</div>
                    </div>
                @endif

                @php $terms = $invoice['terms'] ?? $workspace['quotation_terms'] ?? null; @endphp
                @if(!empty($terms))
                    <div class="section-block">
                        <div class="section-label">Payment Terms</div>
                        <div class="section-text">{{ $terms }}</div>
                    </div>
                @endif

                @if(!empty($workspace['bank_name']) || !empty($workspace['bank_account_number']))
                    <div class="section-block">
                        <div class="section-label">Payment Method</div>
                        @if(!empty($workspace['bank_name']))
                            <div class="bank-row"><span class="bank-k">Bank</span><span class="bank-v">{{ $workspace['bank_name'] }}</span></div>
                        @endif
                        @if(!empty($workspace['bank_account_name']))
                            <div class="bank-row"><span class="bank-k">Account Name</span><span class="bank-v">{{ $workspace['bank_account_name'] }}</span></div>
                        @endif
                        @if(!empty($workspace['bank_account_number']))
                            <div class="bank-row"><span class="bank-k">Account No.</span><span class="bank-v">{{ $workspace['bank_account_number'] }}</span></div>
                        @endif
                        @if(!empty($workspace['bank_swift']))
                            <div class="bank-row"><span class="bank-k">Swift / BIC</span><span class="bank-v">{{ $workspace['bank_swift'] }}</span></div>
                        @endif
                    </div>
                @endif
            </td>

            <td class="col-totals">
                <div class="section-label">Summary</div>
                <div class="total-line">
                    <span class="total-k">Sub Total</span>
                    <span class="total-v">RM {{ number_format((float)($invoice['sub_total'] ?? 0), 2) }}</span>
                </div>

                @if(($invoice['paid_amount'] ?? 0) > 0)
                    <div class="total-line">
                        <span class="total-k">Amount Paid</span>
                        <span class="total-v green">− RM {{ number_format((float)$invoice['paid_amount'], 2) }}</span>
                    </div>
                @endif

                @if(($invoice['deposit_amount'] ?? 0) > 0)
                    <div class="total-line">
                        <span class="total-k">Deposit</span>
                        <span class="total-v">RM {{ number_format((float)$invoice['deposit_amount'], 2) }}</span>
                    </div>
                @endif

                <div class="grand-total">
                    <span class="grand-total-label">Total</span>
                    <span class="grand-total-value">RM {{ number_format((float)($invoice['total'] ?? 0), 2) }}</span>
                </div>

                @php
                    $paidAmt  = (float)($invoice['paid_amount'] ?? 0);
                    $totalAmt = (float)($invoice['total'] ?? 0);
                    $balance  = $totalAmt - $paidAmt;
                @endphp
                @if($paidAmt > 0 && $balance > 0)
                    <div class="balance-row">
                        <span class="balance-label">Balance Due</span>
                        <span class="balance-value">RM {{ number_format($balance, 2) }}</span>
                    </div>
                @endif
            </td>
        </tr>
    </table>

</div>

{{-- ── FOOTER ── --}}
<div class="footer">
    Thank you for your business &mdash; {{ $workspace['company_name'] ?: ($workspace['name'] ?? '') }} &bull; This is a computer-generated document and requires no signature.
</div>

</body>
</html>
