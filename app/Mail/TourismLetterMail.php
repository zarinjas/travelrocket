<?php

namespace App\Mail;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class TourismLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Surat Melancong '.$this->booking->booking_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tourism-letter',
            with: [
                'booking' => $this->booking,
            ],
        );
    }

    public function attachments(): array
    {
        $this->booking->loadMissing(['tenant', 'package', 'leadCustomer', 'passengers']);

        $logoDataUri = null;
        $logoPath = $this->booking->tenant?->logo_path;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $absolutePath = Storage::disk('public')->path($logoPath);
            $mimeType = mime_content_type($absolutePath) ?: 'image/png';
            $logoDataUri = 'data:'.$mimeType.';base64,'.base64_encode((string) file_get_contents($absolutePath));
        }

        $pdf = Pdf::loadView('pdfs.tourism-letter', [
            'booking' => $this->booking,
            'logoDataUri' => $logoDataUri,
        ])->output();

        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $pdf, 'surat-melancong-'.$this->booking->booking_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
