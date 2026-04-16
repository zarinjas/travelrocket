@php
    $customerName = $booking->leadCustomer?->name ?? $booking->leadCustomer?->full_name ?? 'Pelanggan';
    $departureDate = $booking->departure_date?->format('d/m/Y') ?: ($booking->package?->start_date?->format('d/m/Y') ?: '-');
    $returnDate = $booking->return_date?->format('d/m/Y') ?: ($booking->package?->end_date?->format('d/m/Y') ?: '-');
    $companyName = $booking->tenant?->company_name ?: $booking->tenant?->name;
@endphp

<p>Assalamualaikum dan salam sejahtera {{ $customerName }},</p>

<p>
    Dilampirkan Surat Melancong rasmi untuk rujukan pihak tuan/puan.
    Surat ini mengesahkan perjalanan dari {{ $departureDate }} hingga {{ $returnDate }}.
</p>

<p>
    Jika perlukan sebarang pindaan pada maklumat peserta atau butiran perjalanan,
    balas terus email ini dan pihak kami akan bantu dengan segera.
</p>

<p>Terima kasih.</p>

<p>
    Hormat kami,<br>
    {{ $companyName }}<br>
    {{ $booking->tenant?->company_phone ?: '-' }}<br>
    {{ $booking->tenant?->company_email ?: '-' }}
</p>
