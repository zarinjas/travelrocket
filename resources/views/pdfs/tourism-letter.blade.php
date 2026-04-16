<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 28px 34px 40px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #111827;
            line-height: 1.45;
        }

        .top-header {
            width: 100%;
            border-bottom: 1px solid #9ca3af;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .top-header td {
            vertical-align: top;
        }

        .logo-block {
            width: 58%;
        }

        .logo {
            max-height: 64px;
            max-width: 250px;
            margin-bottom: 6px;
        }

        .company-name {
            font-weight: bold;
            font-size: 15px;
            margin: 0;
        }

        .company-sub {
            font-size: 11px;
            color: #374151;
            margin: 2px 0 0;
        }

        .contact-block {
            width: 42%;
            text-align: left;
            font-size: 11px;
            color: #1f2937;
        }

        .contact-line {
            margin: 2px 0;
        }

        .letter-head {
            width: 100%;
            margin-top: 14px;
            margin-bottom: 14px;
        }

        .letter-head td {
            vertical-align: top;
            font-weight: 700;
            font-size: 14px;
        }

        .date-right {
            text-align: right;
        }

        .strong {
            font-weight: 700;
        }

        .subject {
            margin-top: 10px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .body-paragraph {
            margin: 8px 0;
        }

        .indent {
            padding-left: 24px;
        }

        .detail-list {
            margin-top: 8px;
            margin-bottom: 10px;
        }

        .detail-row {
            margin: 2px 0;
        }

        .section-title {
            margin-top: 12px;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .flight-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 12px;
            font-size: 12px;
        }

        .flight-table th,
        .flight-table td {
            border: 1px solid #6b7280;
            padding: 6px;
            text-align: left;
        }

        .flight-table th {
            background: #e5e7eb;
            font-weight: 700;
        }

        .note {
            margin-top: 28px;
            font-size: 11px;
            color: #6b7280;
        }
    </style>
</head>
<body>
@php
    $tenant = $booking->tenant;
    $companyName = $tenant?->company_name ?: $tenant?->name;
    $companyAddress = $tenant?->company_address ?: '-';
    $companyPhone = $tenant?->company_phone ?: '-';
    $companyEmail = $tenant?->company_email ?: '-';
    $companyWebsite = $tenant?->company_website ?: null;

    $destination = $booking->package?->destination ?: '-';
    $departureDate = $booking->departure_date?->format('d F Y') ?: ($booking->package?->start_date?->translatedFormat('d F Y') ?: '-');
    $returnDate = $booking->return_date?->format('d F Y') ?: ($booking->package?->end_date?->translatedFormat('d F Y') ?: '-');
    $durationDays = ($booking->departure_date && $booking->return_date)
        ? max(1, $booking->departure_date->diffInDays($booking->return_date) + 1)
        : null;

    $flightName = $booking->flight_name ?: '-';
    $flightNumber = $booking->flight_number ?: '-';
    $travelDateShort = $booking->departure_date?->format('d/m/Y') ?: ($booking->package?->start_date?->format('d/m/Y') ?: '-');

    $traveller = ($booking->passengers->first()) ?: $booking->leadCustomer;
    $travellerName = $traveller?->name ?: $traveller?->full_name ?: '-';
    $travellerPassport = $traveller?->passport_number ?: '-';
@endphp

    <table class="top-header">
        <tr>
            <td class="logo-block">
                @if(!empty($logoDataUri))
                    <img class="logo" src="{{ $logoDataUri }}" alt="Company Logo">
                @endif
                <p class="company-name">{{ $companyName }}</p>
                <p class="company-sub">{{ $companyAddress }}</p>
            </td>
            <td class="contact-block">
                <p class="contact-line">{{ $companyPhone }}</p>
                <p class="contact-line">{{ $companyEmail }}</p>
                @if($companyWebsite)
                    <p class="contact-line">{{ $companyWebsite }}</p>
                @endif
            </td>
        </tr>
    </table>

    <table class="letter-head">
        <tr>
            <td>KEPADA YANG BERKENAAN</td>
            <td class="date-right">{{ strtoupper(now()->translatedFormat('d M Y')) }}</td>
        </tr>
    </table>

    <p class="strong body-paragraph">ASSALAMUALAIKUM W.B.T</p>
    <p class="strong body-paragraph">TUAN / PUAN,</p>

    <p class="subject">PER: PENGESAHAN MENGIKUTI PAKEJ MELANCONG KE {{ strtoupper($destination) }}</p>

    <p class="body-paragraph">Dengan segala hormatnya kami merujuk kepada perkara di atas.</p>

    <p class="body-paragraph indent">
        2. Sukacita dimaklumkan bahawa penama di bawah akan mengikuti pakej melancong bersama
        <span class="strong">{{ strtoupper($companyName) }}</span> dengan butiran perjalanan seperti berikut:
    </p>

    <div class="detail-list">
        <p class="detail-row">Nama peserta: {{ strtoupper($travellerName) }}</p>
        <p class="detail-row">No Passport: {{ strtoupper($travellerPassport) }}</p>
        <p class="detail-row">Tarikh berlepas: {{ $departureDate }}</p>
        <p class="detail-row">Tarikh pulang: {{ $returnDate }}</p>
        <p class="detail-row">Penerbangan: {{ $flightName }}</p>
        <p class="detail-row">No Flight: {{ $flightNumber }}</p>
        @if($durationDays)
            <p class="detail-row">Tempoh: {{ $durationDays }} hari</p>
        @endif
    </div>

    <p class="section-title">MAKLUMAT PENERBANGAN:</p>
    <table class="flight-table">
        <thead>
            <tr>
                <th>TARIKH</th>
                <th>NO FLIGHT</th>
                <th>SEKTOR</th>
                <th>ETD</th>
                <th>ETA</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $travelDateShort }}</td>
                <td>{{ $flightNumber }}</td>
                <td>{{ strtoupper($destination) }}</td>
                <td>-</td>
                <td>-</td>
            </tr>
        </tbody>
    </table>

    <p class="body-paragraph indent">
        3. Justeru itu, kami memohon kerjasama pihak tuan/puan untuk memberikan pelepasan dan kebenaran kepada individu berkenaan
        untuk menyertai rombongan ini mengikut jadual yang dinyatakan.
    </p>

    <p class="body-paragraph">Sekian, terima kasih atas perhatian dan kerjasama yang diberikan.</p>

    <p class="note">Nota: Surat ini dikeluarkan oleh computer, tidak perlu tandatangan.</p>
</body>
</html>
