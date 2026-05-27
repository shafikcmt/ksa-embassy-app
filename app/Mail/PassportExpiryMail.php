<?php

namespace App\Mail;

use App\Models\Agency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PassportExpiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Agency $agency,
        public Collection $passports
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Passport Expiry Alert — {$this->passports->count()} Candidate(s) — {$this->agency->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.passport-expiry',
        );
    }
}
