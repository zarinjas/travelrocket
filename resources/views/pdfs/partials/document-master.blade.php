<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $documentLabel }} - {{ $documentNumber }}</title>
    <style>
        @page { margin: 40px 48px 50px 48px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        .sheet { width: 100%; }
        .muted { color: #6b7280; }
        .right { text-align: right; }
        .strong { font-weight: 700; }
        .header-table, .meta-table, .summary-table, .footer-table { width: 100%; border-collapse: collapse; }
        .header-table td, .meta-table td, .summary-table td, .footer-table td { vertical-align: top; }

        .brand-logo { max-height: 48px; max-width: 180px; margin-bottom: 8px; }
        .brand-name { font-size: 14px; font-weight: 800; margin: 0; letter-spacing: -0.01em; color: #111827; }
        .brand-sub { margin: 2px 0 0; font-size: 9px; font-weight: 600; color: #4b5563; text-transform: uppercase; }
        .brand-address { margin-top: 6px; font-size: 9px; color: #6b7280; white-space: pre-line; line-height: 1.3; }

        .doc-label { font-size: 24px; font-weight: 800; letter-spacing: 0.05em; margin: 0; line-height: 1; color: #111827; }
        .doc-number { margin-top: 6px; font-size: 11px; font-weight: 700; color: #374151; }

        .section { margin-top: 32px; border-top: 1px solid #f3f4f6; padding-top: 16px; }
        .meta-heading { font-size: 9px; margin: 0 0 4px; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.05em; }
        .meta-name { margin: 0; font-size: 12px; font-weight: 700; color: #111827; }
        .meta-note { margin: 8px 0 0; font-size: 10px; color: #4b5563; }
        .meta-date { font-size: 9px; margin: 0 0 2px; font-weight: 700; text-transform: uppercase; color: #9ca3af; }
        .meta-date-value { font-size: 11px; margin: 0; font-weight: 600; color: #111827; }
        .subject-label { margin: 12px 0 2px; font-size: 9px; font-weight: 700; text-transform: uppercase; color: #9ca3af; }
        .subject-value { margin: 0; font-size: 11px; font-weight: 600; color: #111827; }

        .line-items { margin-top: 24px; width: 100%; border-collapse: collapse; }
        .line-items thead th {
            border-bottom: 2px solid #111827;
            color: #111827;
            font-size: 9px;
            font-weight: 800;
            text-align: left;
            padding: 8px 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .line-items tbody td {
            border-bottom: 1px solid #f3f4f6;
            padding: 12px 4px;
            vertical-align: top;
            font-size: 10px;
        }
        .col-no { width: 5%; color: #9ca3af; }
        .col-desc { width: 55%; }
        .col-qty { width: 10%; text-align: right; }
        .col-rate { width: 15%; text-align: right; }
        .col-amount { width: 15%; text-align: right; font-weight: 700; }
        
        .item-title { font-size: 11px; font-weight: 700; margin: 0 0 4px; color: #111827; }
        .item-lines { margin: 0; padding: 0; list-style: none; }
        .item-lines li { margin: 1px 0; color: #6b7280; font-size: 9px; }

        .summary-wrap { margin-top: 24px; width: 100%; }
        .summary-table {
            margin-left: auto;
            width: 280px;
        }
        .summary-table td {
            padding: 6px 8px;
            font-size: 11px;
        }
        .summary-table .label { text-align: right; color: #6b7280; font-weight: 500; }
        .summary-table .value { text-align: right; color: #111827; font-weight: 600; }
        .summary-table .total-row td {
            font-weight: 800;
            background: #f9fafb;
            font-size: 13px;
            border-top: 2px solid #111827;
            padding-top: 10px;
        }
        
        .notes { margin-top: 32px; }
        .notes-title { font-size: 8px; font-weight: 800; text-transform: uppercase; color: #9ca3af; margin: 0 0 6px; letter-spacing: 0.05em; }
        .notes-subtitle { margin: 12px 0 4px; font-size: 9px; font-weight: 700; color: #111827; text-transform: uppercase; }
        .notes-content { margin: 0; font-size: 9px; color: #4b5563; white-space: pre-line; line-height: 1.5; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding-bottom: 30px; border-top: 1px solid #f3f4f6; padding-top: 10px; }
        .footer-table td { font-size: 8px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="sheet">
        <table class="header-table">
            <tr>
                <td style="width:60%;">
                    @if(!empty($logoDataUri))
                        <img src="{{ $logoDataUri }}" alt="Company Logo" class="brand-logo">
                    @endif
                    <p class="brand-name">{{ $companyName }}</p>
                    <p class="brand-sub">{{ $companySubName }}</p>
                    <p class="brand-address">{{ $companyAddressBlock }}</p>
                </td>
                <td class="right" style="width:40%;">
                    <p class="doc-label">{{ $documentLabel }}</p>
                    <p class="doc-number"># {{ $documentNumber }}</p>
                </td>
            </tr>
        </table>

        <div class="section">
            <table class="meta-table">
                <tr>
                    <td style="width:64%; padding-right:20px;">
                        <p class="meta-heading">Bill To</p>
                        <p class="meta-name">{{ $billToName }}</p>

                        <p class="subject-label">Subject :</p>
                        <p class="subject-value">{{ $subject }}</p>
                    </td>
                    <td style="width:36%;">
                        <p class="meta-date">{{ $dateLabel }} :</p>
                        <p class="meta-date-value right">{{ $dateValue }}</p>
                    </td>
                </tr>
            </table>
        </div>

        <table class="line-items">
            <thead>
                <tr>
                    <th class="col-no">#</th>
                    <th class="col-desc">Item &amp; Description</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-rate">Rate</th>
                    <th class="col-amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td class="col-no">{{ $item['no'] }}</td>
                        <td class="col-desc">
                            <p class="item-title">{{ $item['title'] }}</p>
                            @if(!empty($item['lines']))
                                <ul class="item-lines">
                                    @foreach($item['lines'] as $line)
                                        <li>- {{ $line }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td class="col-qty">{{ $item['qty'] }}</td>
                        <td class="col-rate">{{ $item['rate'] }}</td>
                        <td class="col-amount">{{ $item['amount'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary-wrap">
            <table class="summary-table">
                <tr>
                    <td class="label">Sub Total</td>
                    <td class="value">{{ $subTotalValue }}</td>
                </tr>
                <tr class="total-row">
                    <td class="label">Total</td>
                    <td class="value">{{ $totalValue }}</td>
                </tr>
            </table>
        </div>

        <div class="notes">
            <p class="notes-title">Notes</p>

            <p class="notes-subtitle">Payment Terms:</p>
            <p class="notes-content">{{ $paymentTerms }}</p>

            <p class="notes-subtitle">Payment Method</p>
            <p class="notes-content">{{ $paymentMethod }}</p>
        </div>
    </div>

    <div class="footer">
        <div class="footer-line"></div>
        <table class="footer-table">
            <tr>
                <td></td>
                <td class="right">1</td>
            </tr>
        </table>
    </div>
</body>
</html>
