<?php

namespace App\Mail;

use App\Models\EmailVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmailVerification $verification,
        public string $userName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de Verificación de Email - Condominio',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-code',
            with: [
                'code' => $this->verification->code,
                'userName' => $this->userName,
                'expiresAt' => $this->verification->expires_at->format('H:i'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
