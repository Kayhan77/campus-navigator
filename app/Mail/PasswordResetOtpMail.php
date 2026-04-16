<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Config;

class PasswordResetOtpMail extends Mailable
{
    public function __construct(
        public readonly string $otp,
        public readonly string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Password Reset Code – ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-otp',
            with: [
                'otp'           => $this->otp,
                'recipientName' => $this->recipientName,
                'appName'       => config('app.name'),
                'expiresIn'     => 10,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}