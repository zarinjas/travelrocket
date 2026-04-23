<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @font-face { font-family: 'Poppins'; font-weight: 400; src: url("{{ public_path('fonts/poppins/Poppins-Regular.woff') }}") format('woff'); }
        @font-face { font-family: 'Poppins'; font-weight: 500; src: url("{{ public_path('fonts/poppins/Poppins-Medium.woff') }}") format('woff'); }
        @font-face { font-family: 'Poppins'; font-weight: 600; src: url("{{ public_path('fonts/poppins/Poppins-SemiBold.woff') }}") format('woff'); }
        @font-face { font-family: 'Poppins'; font-weight: 700; src: url("{{ public_path('fonts/poppins/Poppins-Bold.woff') }}") format('woff'); }

        @page { margin: 0; size: A4; }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', DejaVu Sans, Arial, sans-serif;
            color: #1f2937;
            font-size: 9.5px;
            line-height: 1.5;
            background: #ffffff;
        }

        /* ── HEADER ── */
        .page-header { padding: 32px 44px 20px; background: #ffffff; }
        .hdr-tbl { width: 100%; border-collapse: collapse; }
        .hdr-left { width: 55%; vertical-align: top; }
        .hdr-right { width: 45%; vertical-align: top; text-align: right; }

        .logo-img { height: 50px; width: auto; max-width: 130px; margin-bottom: 10px; display: block; }
        .co-name { font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 3px; }
        .co-sub { font-size: 8.5px; color: #6b7280; line-height: 1.9; }

        .doc-type { font-size: 36px; font-weight: 700; color: #111827; letter-spacing: -1px; line-height: 1; margin-bottom: 6px; }
        .doc-ref { font-size: 9.5px; color: #6b7280; margin-bottom: 12px; }
        .doc-ref b { color: #374151; font-weight: 600; }
        .total-label { font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #6b7280; }
        .total-amount { font-size: 22px; font-weight: 700; color: #111827; margin-top: 2px; }

        /* ── DIVIDER ── */
        .hr { border: none; border-top: 1px solid #e5e7eb; margin: 0 44px; }

        /* ── BILL TO + META ── */
        .info-section { padding: 16px 44px 18px; }
        .info-tbl { width: 100%; border-collapse: collapse; }
        .info-left { width: 45%; vertical-align: top; padding-right: 16px; }
        .info-right { width: 55%; vertical-align: top; }

        .band-lbl { font-size: 7.5px; font-weight: 600; letter-spacing: 1.2px; text-transform: uppercase; color: #9ca3af; margin-bottom: 5px; }
        .cname { font-size: 12px; font-weight: 600; color: #111827; margin-bottom: 3px; }
        .csub { font-size: 8.5px; color: #6b7280; line-height: 1.8; }

        .meta-tbl { width: 100%; border-collapse: collapse; }
        .meta-tbl td { padding: 3.5px 4px; font-size: 9px; border-bottom: 1px solid #f3f4f6; }
        .meta-tbl tr:last-child td { border-bottom: none; }
        .mk { color: #9ca3af; text-align: right; padding-right: 14px; white-space: nowrap; width: 42%; }
        .mv { color: #374151; font-weight: 500; text-align: right; }

        .badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 8px; font-weight: 600; letter-spacing: 0.4px; text-transform: uppercase; }
        .b-draft   { background: #f3f4f6; color: #374151; }
        .b-sent    { background: #dbeafe; color: #1e3a8a; }
        .b-closed  { background: #dcfce7; color: #14532d; }
        .b-expired { background: #fee2e2; color: #7f1d1d; }

        /* ── ITEMS TABLE ── */
        .content { padding: 0 44px 32px; }
        .items-tbl { width: 100%; border-collapse: collapse; }
        .items-tbl thead tr { background: #1e3a5f; }
        .items-tbl thead th {
            padding: 9px 12px;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: rgba(255,255,255,0.9);
            text-align: left;
        }
        .items-tbl thead th.r { text-align: right; }
        .items-tbl thead th.c { text-align: center; }
        .items-tbl tbody tr { border-bottom: 1px solid #f3f4f6; }
        .items-tbl tbody tr:nth-child(even) { background: #fafafa; }
        .items-tbl tbody td { padding: 10px 12px; font-size: 9.5px; vertical-align: top; color: #1f2937; }
        .items-tbl tbody td.r { text-align: right; }
        .items-tbl tbody td.c { text-align: center; color: #6b7280; }
        .iname { font-weight: 600; color: #111827; margin-bottom: 2px; }
        .idesc { font-size: 8.5px; color: #6b7280; line-height: 1.6; white-space: pre-wrap; }

        /* ── TOTALS ── */
        .totals-outer { width: 100%; border-collapse: collapse; }
        .totals-spacer { width: 55%; vertical-align: top; }
        .totals-cell { width: 45%; vertical-align: top; }
        .totals-tbl { width: 100%; border-collapse: collapse; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; }
        .totals-tbl td { padding: 7px 14px; font-size: 9.5px; border-bottom: 1px solid #f3f4f6; }
        .totals-tbl tr:last-child td { border-bottom: none; }
        .tk { color: #6b7280; }
        .tv { font-weight: 600; color: #111827; text-align: right; }
        .total-row td { background: #f3f4f6; font-weight: 700; color: #111827; font-size: 10px; border-top: 2px solid #d1d5db; border-bottom: none; }
        .total-row td.tv { text-align: right; }

        /* ── NOTES / TERMS / BANK ── */
        .sections-wrap { margin-top: 22px; }
        .sec-block { margin-bottom: 14px; }
        .sec-label { font-size: 8px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: #9ca3af; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; margin-bottom: 7px; }
        .sec-text { font-size: 9px; color: #374151; line-height: 1.7; white-space: pre-wrap; }

        .bank-tbl { border-collapse: collapse; }
        .bank-tbl td { padding: 2px 0; font-size: 9px; }
        .bk { color: #9ca3af; width: 110px; padding-right: 8px; }
        .bv { color: #374151; font-weight: 500; }

        /* ── FOOTER ── */
        .footer { padding: 10px 44px; background: #f8fafc; border-top: 1px solid #e5e7eb; font-size: 7.5px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>

@php
    $status = $quotation['status'] ?? 'Draft';
    $bcls   = match(strtolower($status)) {
        'sent'    => 'b-sent',
        'closed'  => 'b-closed',
        'expired' => 'b-expired',
        default   => 'b-draft',
    };
@endphp

{{-- HEADER --}}
<div class="page-header">
    <table class="hdr-tbl">
        <tr>
            <td class="hdr-left">
                @if(!empty($workspace['logo_url']))
                    <img src="{{ $workspace['logo_url'] }}" alt="" class="logo-img">
                @endif
                <div class="co-name">{{ $workspace['company_name'] ?: ($workspace['name'] ?? '') }}</div>
                <div class="co-sub">
                    @if(!empty($workspace['company_address'])){{ $workspace['company_address'] }}<br>@endif
                    @if(!empty($workspace['company_phone'])){{ $workspace['company_phone'] }}<br>@endif
                    @if(!empty($workspace['company_email'])){{ $workspace['company_email'] }}@endif
                </div>
            </td>
            <td class="hdr-right">
                <div class="doc-type">QUOTATION</div>
                <div class="doc-ref">Quotation # <b>{{ $quotation['public_id'] ?? '—' }}</b></div>
                <div class="total-label">Total Amount</div>
                <div class="total-amount">RM {{ number_format((float)($quotation['total'] ?? 0), 2) }}</div>
            </td>
        </tr>
    </table>
</div>

<hr class="hr">

{{-- BILL TO + META --}}
<div class="info-section">
    <table class="info-tbl">
        <tr>
            <td class="info-left">
                <div class="band-lbl">Bill To</div>
                @if(!empty($quotation['customer']))
                    <div class="cname">{{ $quotation['customer']['name'] ?? '—' }}</div>
                    <div class="csub">
                        @if(!empty($quotation['customer']['phone'])){{ $quotation['customer']['phone'] }}<br>@endif
                        {{ $quotation['customer']['email'] ?? '' }}
                    </div>
                @else
                    <div class="cname">{{ ($quotation['manual_customer_data']['name'] ?? null) ?: 'Walk-in Customer' }}</div>
                    <div class="csub">
                        @if(!empty($quotation['manual_customer_data']['phone'])){{ $quotation['manual_customer_data']['phone'] }}<br>@endif
                        {{ $quotation['manual_customer_data']['email'] ?? '' }}
                    </div>
                @endif
            </td>
            <td class="info-right">
                <table class="meta-tbl">
                    <tr>
                        <td class="mk">Date :</td>
                        <td class="mv">{{ !empty($quotation['created_at']) ? \Carbon\Carbon::parse($quotation['created_at'])->format('d M Y') : '—' }}</td>
                    </tr>
                    @if(!empty($quotation['expiry_date']))
                    <tr>
                        <td class="mk">Valid Until :</td>
                        <td class="mv">{{ \Carbon\Carbon::parse($quotation['expiry_date'])->format('d M Y') }}</td>
                    </tr>
                    @endif
                    @if(!empty($quotation['subject']))
                    <tr>
                        <td class="mk">Subject :</td>
                        <td class="mv">{{ $quotation['subject'] }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="mk">Status :</td>
                        <td class="mv"><span class="badge {{ $bcls }}">{{ $status }}</span></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

{{-- ITEMS + TOTALS + SECTIONS --}}
<div class="content">

    <table class="items-tbl">
        <thead>
            <tr>
                <th class="c" style="width:5%">#</th>
                <th style="width:55%">Item &amp; Description</th>
                <th class="c" style="width:8%">Qty</th>
                <th class="r" style="width:15%">Rate</th>
                <th class="r" style="width:17%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($quotation['items'] ?? []) as $i => $item)
                @php $lines = explode("\n", $item['description'] ?? ''); @endphp
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>
                        <div class="iname">{{ $lines[0] ?? '—' }}</div>
                        @if(count($lines) > 1)
                            <div class="idesc">{{ implode("\n", array_slice($lines, 1)) }}</div>
                        @endif
                    </td>
                    <td class="c">{{ $item['qty'] ?? 1 }}</td>
                    <td class="r">RM {{ number_format((float)($item['rate'] ?? 0), 2) }}</td>
                    <td class="r" style="font-weight:600;">RM {{ number_format((float)($item['amount'] ?? ($item['qty'] * $item['rate'])), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#9ca3af; padding:20px 12px;">No items added.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- TOTALS (right-aligned, matches reference) --}}
    <table class="totals-outer">
        <tr>
            <td class="totals-spacer">&nbsp;</td>
            <td class="totals-cell">
                <table class="totals-tbl">
                    <tr>
                        <td class="tk">Sub Total</td>
                        <td class="tv">RM {{ number_format((float)($quotation['sub_total'] ?? 0), 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total</td>
                        <td class="tv">RM {{ number_format((float)($quotation['total'] ?? 0), 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- NOTES / TERMS / BANK --}}
    <div class="sections-wrap">
        @if(!empty($quotation['notes']))
            <div class="sec-block">
                <div class="sec-label">Notes</div>
                <div class="sec-text">{{ $quotation['notes'] }}</div>
            </div>
        @endif

        @php $terms = $quotation['terms'] ?? $workspace['quotation_terms'] ?? null; @endphp
        @if(!empty($terms))
            <div class="sec-block">
                <div class="sec-label">Terms &amp; Conditions</div>
                <div class="sec-text">{{ $terms }}</div>
            </div>
        @endif

        @if(!empty($workspace['bank_name']) || !empty($workspace['bank_account_number']))
            <div class="sec-block">
                <div class="sec-label">Payment Details</div>
                <table class="bank-tbl">
                    @if(!empty($workspace['bank_name']))
                        <tr><td class="bk">Bank</td><td class="bv">{{ $workspace['bank_name'] }}</td></tr>
                    @endif
                    @if(!empty($workspace['bank_account_name']))
                        <tr><td class="bk">Account Name</td><td class="bv">{{ $workspace['bank_account_name'] }}</td></tr>
                    @endif
                    @if(!empty($workspace['bank_account_number']))
                        <tr><td class="bk">Account No.</td><td class="bv">{{ $workspace['bank_account_number'] }}</td></tr>
                    @endif
                    @if(!empty($workspace['bank_swift']))
                        <tr><td class="bk">Swift / BIC</td><td class="bv">{{ $workspace['bank_swift'] }}</td></tr>
                    @endif
                </table>
            </div>
        @endif
    </div>

</div>

{{-- FOOTER --}}
<div class="footer">
    This quotation is valid until the expiry date &bull; Subject to availability &bull; {{ $workspace['company_name'] ?: ($workspace['name'] ?? '') }}
</div>

</body>
</html>
