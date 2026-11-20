<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class WeeklyCommercialReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $ccAddresses
     */
    public function __construct(
        public Carbon $periodStart,
        public Carbon $periodEnd,
        public array $summary,
        public string $pdfPath,
        public string $pdfFilename,
        public array $ccAddresses = [],
        public string $recipientName = '',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reporte semanal de cotizaciones Concreplus — '
                .$this->periodStart->format('d/m/Y').' al '.$this->periodEnd->format('d/m/Y'),
            cc: array_map(fn (string $email) => new Address($email), $this->ccAddresses),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reports.weekly-commercial',
            with: [
                'periodStart' => $this->periodStart,
                'periodEnd' => $this->periodEnd,
                'summary' => $this->summary,
                'recipientName' => $this->recipientName,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! file_exists($this->pdfPath)) {
            return [];
        }

        return [
            Attachment::fromPath($this->pdfPath)
                ->as($this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
