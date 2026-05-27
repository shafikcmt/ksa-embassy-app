<?php

namespace App\Mail;

use App\Models\Agency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LicenseExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Agency $agency,
        public int $daysRemaining
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Agency License Expiring in {$this->daysRemaining} Day(s) — {$this->agency->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.license-expiring',
        );
    }
}
