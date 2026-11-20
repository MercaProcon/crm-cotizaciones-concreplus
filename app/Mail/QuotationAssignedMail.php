<?php

namespace App\Mail;

use App\Models\Assignment;
use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public Assignment $assignment,
        public bool $isReassignment,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isReassignment
                ? __('Cotización reasignada a ti — :client', ['client' => $this->quotation->client_name])
                : __('Nueva cotización asignada — :client', ['client' => $this->quotation->client_name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotations.assigned',
            with: [
                'quotation' => $this->quotation,
                'assignment' => $this->assignment,
                'isReassignment' => $this->isReassignment,
            ],
        );
    }
}
