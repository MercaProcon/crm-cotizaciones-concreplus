<?php

namespace App\Mail;

use App\Models\FacadeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class FacadeRequestRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FacadeRequest $facadeRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de rotulación rechazada — '.$this->facadeRequest->folio,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.facades.rejected',
            with: [
                'facadeRequest' => $this->facadeRequest,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->facadeRequest->pdf_path || ! Storage::disk('local')->exists($this->facadeRequest->pdf_path)) {
            return [];
        }

        return [
            Attachment::fromPath(Storage::disk('local')->path($this->facadeRequest->pdf_path))
                ->as($this->facadeRequest->folio.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
