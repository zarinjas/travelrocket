<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $package['name'] }} - Tour Confirmation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            line-height: 1.5;
            font-size: 11px;
        }
        .page {
            width: 210mm;
            height: 297mm;
            padding: 20mm;
            background: white;
        }

        /* Sections */
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            background-color: #edf2f7;
            padding: 8px 10px;
            margin-bottom: 10px;
            border-left: 4px solid #4299e1;
        }
        .section-content {
            margin-left: 10px;
        }

        /* Group Confirmation */
        .group-info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .group-info-col {
            display: table-cell;
            width: 25%;
            padding-right: 10px;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #2d3748;
            font-size: 10px;
            margin-bottom: 2px;
        }
        .info-value {
            color: #4a5568;
            font-size: 11px;
        }

        /* Itinerary Table */
        .itinerary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .itinerary-table th {
            background-color: #cbd5e0;
            color: #2d3748;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #a0aec0;
        }
        .itinerary-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e0;
            font-size: 10px;
        }
        .itinerary-table tr:nth-child(even) {
            background-color: #f7fafc;
        }

        /* Inclusions/Exclusions */
        .two-column {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .column {
            display: table-cell;
            width: 48%;
            padding-right: 15px;
            vertical-align: top;
        }
        .list-title {
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 5px;
            font-size: 11px;
        }
        .list-item {
            margin: 3px 0 3px 15px;
            font-size: 10px;
        }
        .list-item:before {
            content: "•";
            margin-right: 5px;
        }

        /* General Information */
        .general-info-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .general-info-table td {
            padding: 8px;
            font-size: 10px;
            line-height: 1.6;
            border: 1px solid #cbd5e0;
        }
        .general-info-table td:first-child {
            width: 25%;
            background-color: #f7fafc;
            font-weight: bold;
            color: #2d3748;
        }

        /* Rooming List */
        .rooming-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .rooming-table th {
            background-color: #cbd5e0;
            color: #2d3748;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #a0aec0;
        }
        .rooming-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e0;
            font-size: 10px;
        }
        .rooming-table tr:nth-child(even) {
            background-color: #f7fafc;
        }
        .room-header {
            background-color: #edf2f7;
            font-weight: bold;
            color: #2d3748;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #cbd5e0;
            text-align: center;
            font-size: 9px;
            color: #718096;
        }

        /* Print specific */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .page {
                page-break-after: always;
                margin: 0;
                padding: 20mm;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 25px; border-bottom: 2px solid #2d3748; padding-bottom: 15px;">
            <tr>
                <td style="width: 35%; text-align: left; vertical-align: top;">
                    @if($tenant['logo_path'])
                        <img src="{{ storage_path('app/' . $tenant['logo_path']) }}" alt="Company Logo" style="max-width: 100px; height: auto; margin-bottom: 10px;">
                    @endif
                    <div style="font-size: 10px; line-height: 1.4;">
                        <strong style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 3px;">{{ $tenant['company_name'] ?? $tenant['name'] }}</strong>
                        @if($tenant['company_address'])
                            <p style="margin: 2px 0;">{{ $tenant['company_address'] }}</p>
                        @endif
                        @if($tenant['company_phone'])
                            <p style="margin: 2px 0;">{{ $tenant['company_phone'] }}</p>
                        @endif
                        @if($tenant['company_email'])
                            <p style="margin: 2px 0;">{{ $tenant['company_email'] }}</p>
                        @endif
                        @if($tenant['company_website'])
                            <p style="margin: 2px 0;">{{ $tenant['company_website'] }}</p>
                        @endif
                    </div>
                </td>
                <td style="width: 65%; text-align: right; vertical-align: top;">
                    <div style="font-size: 24px; font-weight: bold; color: #2d3748; margin-bottom: 5px;">Tour Confirmation</div>
                    <div style="font-size: 13px; color: #718096; margin-bottom: 8px;">& Rooming List</div>
                    <p style="font-size: 10px; color: #a0aec0; margin-top: 10px;">
                        Generated: {{ now()->format('d/m/Y H:i') }}
                    </p>
                </td>
            </tr>
        </table>

        <!-- 1. Group Confirmation -->
        <div class="section">
            <div class="section-title">Group Confirmation</div>
            <div class="section-content">
                <div class="group-info-grid">
                    <div class="group-info-col">
                        <div class="info-label">Package Name</div>
                        <div class="info-value">{{ $package['name'] }}</div>
                    </div>
                    <div class="group-info-col">
                        <div class="info-label">Destination</div>
                        <div class="info-value">{{ $package['destination'] ?? '–' }}</div>
                    </div>
                    <div class="group-info-col">
                        <div class="info-label">Start Date</div>
                        <div class="info-value">{{ $package['start_date'] ? \Carbon\Carbon::parse($package['start_date'])->format('d/m/Y') : '–' }}</div>
                    </div>
                    <div class="group-info-col">
                        <div class="info-label">End Date</div>
                        <div class="info-value">{{ $package['end_date'] ? \Carbon\Carbon::parse($package['end_date'])->format('d/m/Y') : '–' }}</div>
                    </div>
                </div>
                @if($package['flight_info'])
                    <div style="margin-top: 10px;">
                        <div class="info-label">Flight Information</div>
                        <div class="info-value">{{ implode(' | ', array_filter((array)$package['flight_info'])) }}</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. Proposed Travel Itinerary -->
        @if($package['itinerary_days'])
        <div class="section">
            <div class="section-title">Proposed Travel Itinerary</div>
            <div class="section-content">
                <table class="itinerary-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Itinerary</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($package['itinerary_days'] as $index => $day)
                        <tr>
                            <td style="width: 10%; text-align: center; font-weight: bold;">Day {{ $index + 1 }}</td>
                            <td>{{ $day['title'] ?? $day ?? '–' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- 3. Package Inclusions & Exclusions -->
        <div class="section">
            <div class="section-title">Package Inclusions & Exclusions</div>
            <div class="section-content">
                <div class="two-column">
                    <div class="column">
                        <div class="list-title">Inclusions</div>
                        @if($package['inclusions'])
                            @foreach($package['inclusions'] as $item)
                                <div class="list-item">{{ $item }}</div>
                            @endforeach
                        @else
                            <div class="list-item">Not specified</div>
                        @endif
                    </div>
                    <div class="column">
                        <div class="list-title">Exclusions</div>
                        @if($package['exclusions'])
                            @foreach($package['exclusions'] as $item)
                                <div class="list-item">{{ $item }}</div>
                            @endforeach
                        @else
                            <div class="list-item">Not specified</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. General Information -->
        <div class="section">
            <div class="section-title">General Information</div>
            <div class="section-content">
                <table class="general-info-table">
                    <tr>
                        <td>Weather</td>
                        <td>[Space for weather information]</td>
                    </tr>
                    <tr>
                        <td>Prayer Times</td>
                        <td>[Prayer times to be added based on destination]</td>
                    </tr>
                    @if($package['visa_info'])
                    <tr>
                        <td>Visa Information</td>
                        <td>{{ implode(', ', (array)$package['visa_info']) }}</td>
                    </tr>
                    @endif
                    @if($package['meal_plan'])
                    <tr>
                        <td>Meal Plan</td>
                        <td>{{ $package['meal_plan'] }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- 5. Final Rooming List -->
        <div class="section">
            <div class="section-title">Final Rooming List</div>
            <div class="section-content">
                @if(count($savedRooms) > 0)
                    @foreach($savedRooms as $room)
                    <div style="margin-bottom: 15px;">
                        <div style="background-color: #edf2f7; padding: 6px 8px; margin-bottom: 5px; font-weight: bold; border-left: 4px solid #4299e1;">
                            {{ $room['label'] ?? 'Room' }}
                            <span style="float: right; font-size: 10px; font-weight: normal;">
                                {{ ucfirst($room['type'] ?? '') }} Room
                                @if(!empty($room['passengers']))
                                    ({{ count($room['passengers']) }} guests)
                                @endif
                            </span>
                        </div>
                        @if(!empty($room['passengers']))
                            <table class="rooming-table" style="margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Guest Name</th>
                                        <th>Passport Number</th>
                                        <th>Date of Birth</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($room['passengers'] as $passengerIndex => $passengerId)
                                        @php
                                            $passenger = collect($passengers)->firstWhere('id', $passengerId);
                                        @endphp
                                        @if($passenger)
                                        <tr>
                                            <td style="text-align: center;">{{ $passengerIndex + 1 }}</td>
                                            <td>{{ $passenger['name'] ?? '–' }}</td>
                                            <td>{{ $passenger['passport_number'] ?? '–' }}</td>
                                            <td>{{ $passenger['date_of_birth'] ? \Carbon\Carbon::parse($passenger['date_of_birth'])->format('d/m/Y') : '–' }}</td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p style="font-size: 10px; color: #a0aec0; padding: 6px 8px;">No passengers assigned</p>
                        @endif
                    </div>
                    @endforeach
                @else
                    <p style="font-size: 11px; color: #718096;">No rooming list data available.</p>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This document was generated automatically. For inquiries, contact {{ $tenant['company_email'] ?? $tenant['company_name'] }}</p>
        </div>
    </div>
</body>
</html>
