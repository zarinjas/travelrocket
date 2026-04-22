<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0.5in;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            font-size: 10px;
            line-height: 1.4;
            width: 100%;
        }
        .container {
            width: 100%;
        }
        .items-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .items-table thead {
            background-color: #2d3748;
            color: white;
        }
        .items-table th {
            padding: 10px 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        .col-number {
            width: 5%;
            text-align: center;
        }
        .col-description {
            width: 50%;
        }
        .col-qty {
            width: 10%;
            text-align: center;
        }
        .col-rate {
            width: 17%;
            text-align: right;
        }
        .col-amount {
            width: 18%;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Master Header -->
        <table style="width: 100%; table-layout: fixed; margin-bottom: 20px; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; text-align: left; vertical-align: top;">
                    @if(($workspace['logo_url'] ?? null))
                        <img src="{{ $workspace['logo_url'] }}" alt="Logo" style="width: 40px; height: 40px; margin-bottom: 8px; display: block;">
                    @endif
                    <div style="font-weight: bold; font-size: 13px; margin-bottom: 2px;">{{ $workspace['name'] ?? 'Company Name' }}</div>
                    <div style="font-size: 9px; color: #666; line-height: 1.4;">
                        Level 8, Menara Travel<br>
                        Kuala Lumpur, Malaysia
                    </div>
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <div style="font-size: 32px; font-weight: bold; margin-bottom: 8px; color: #1f2937;">QUOTATION</div>
                    <div style="color: #666; font-size: 10px; margin-bottom: 8px;">#{{ $quotation['public_id'] ?? '-' }}</div>
                    <div style="font-size: 9px; line-height: 1.6; color: #333;">
                        <div><strong>Quote Date:</strong> {{ ($quotation['created_at'] ?? null) ? \Carbon\Carbon::parse($quotation['created_at'])->format('d M Y') : '-' }}</div>
                        <div><strong>Expiry Date:</strong> {{ ($quotation['expiry_date'] ?? null) ? \Carbon\Carbon::parse($quotation['expiry_date'])->format('d M Y') : '-' }}</div>
                        @if(($quotation['subject'] ?? null))
                            <div><strong>Subject:</strong> {{ $quotation['subject'] }}</div>
                        @endif
                        <div><strong>Status:</strong> {{ $quotation['status'] }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Bill To Section -->
        <table style="width: 100%; table-layout: fixed; margin-bottom: 15px; border-collapse: collapse;">
            <tr>
                <td style="width: 100%; text-align: left;">
                    <div style="font-weight: bold; font-size: 9px; color: #999; text-transform: uppercase; margin-bottom: 8px;">Bill To</div>
                    @if(($quotation['customer'] ?? null))
                        <div style="font-weight: bold; font-size: 11px; margin-bottom: 4px;">{{ $quotation['customer']['name'] ?? '-' }}</div>
                        @if(($quotation['customer']['phone'] ?? null))
                            <div style="font-size: 9px; color: #555;">{{ $quotation['customer']['phone'] }}</div>
                        @endif
                        <div style="font-size: 9px; color: #555;">{{ $quotation['customer']['email'] ?? '-' }}</div>
                    @else
                        <div style="font-weight: bold; font-size: 11px; margin-bottom: 4px;">{{ ($quotation['manual_customer_data']['name'] ?? null) ?: 'Walk-in Customer' }}</div>
                        @if(($quotation['manual_customer_data']['address'] ?? null))
                            <div style="font-size: 9px; color: #555; white-space: pre-wrap;">{{ $quotation['manual_customer_data']['address'] }}</div>
                        @endif
                    @endif
                </td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-number">#</th>
                    <th class="col-description">Item & Description</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-rate">Rate</th>
                    <th class="col-amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($quotation['items'] ?? []) as $index => $item)
                    <tr>
                        <td class="col-number">{{ $index + 1 }}</td>
                        <td class="col-description">
                            @php $descLines = explode("\n", ($item['description'] ?? '')); @endphp
                            <div>{{ $descLines[0] ?? '-' }}</div>
                            @if(count($descLines) > 1)
                                <div style="color: #999; font-size: 9px; margin-top: 2px;">{{ implode("\n", array_slice($descLines, 1)) }}</div>
                            @endif
                        </td>
                        <td class="col-qty">{{ ($item['qty'] ?? 0) }}</td>
                        <td class="col-rate">RM {{ number_format(($item['rate'] ?? 0), 2) }}</td>
                        <td class="col-amount">RM {{ number_format(($item['amount'] ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary & Terms Layout -->
        <table style="width: 100%; table-layout: fixed; margin-top: 15px; border-collapse: collapse;">
            <tr>
                <!-- Left: Notes/Terms -->
                <td style="width: 55%; vertical-align: top; text-align: left;">
                    @if(($quotation['notes'] ?? null) || ($quotation['terms'] ?? null))
                        <table style="width: 100%; border-collapse: collapse;">
                            @if(($quotation['notes'] ?? null))
                                <tr>
                                    <td style="padding: 0 0 12px 0; vertical-align: top;">
                                        <div style="font-size: 9px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 6px;">Notes</div>
                                        <div style="font-size: 9px; color: #666; line-height: 1.5; white-space: pre-wrap;">{{ $quotation['notes'] }}</div>
                                    </td>
                                </tr>
                            @endif
                            @if(($quotation['terms'] ?? null))
                                <tr>
                                    <td style="padding: 12px 0 0 0; vertical-align: top;">
                                        <div style="font-size: 9px; font-weight: bold; color: #999; text-transform: uppercase; margin-bottom: 6px;">Terms & Conditions</div>
                                        <div style="font-size: 9px; color: #666; line-height: 1.5; white-space: pre-wrap;">{{ $quotation['terms'] }}</div>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    @endif
                </td>

                <!-- Right: Summary -->
                <td style="width: 45%; vertical-align: top; padding-left: 20px;">
                    <table style="width: 100%; border-collapse: collapse; border: none; table-layout: fixed;">
                        <tr>
                            <td style="width: 60%; text-align: left; font-size: 10px; color: #666; padding-bottom: 8px;">Sub Total</td>
                            <td style="width: 40%; text-align: right; font-size: 10px; color: #333; padding-bottom: 8px;">RM {{ number_format(($quotation['sub_total'] ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding: 12px; background-color: #f3f4f6; border-radius: 3px;">
                                <table style="width: 100%; border-collapse: collapse; border: none; table-layout: fixed;">
                                    <tr>
                                        <td style="width: 60%; font-size: 10px; font-weight: bold; text-transform: uppercase; color: #555;">Total</td>
                                        <td style="width: 40%; text-align: right; font-size: 14px; font-weight: bold; color: #1f2937;">RM {{ number_format(($quotation['total'] ?? 0), 2) }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
