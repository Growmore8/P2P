<?php

namespace App\Mail;

use App\Models\OtpCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public string $purpose, public ?string $name = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->code . ' is your SRM Fintech ' . ($this->purpose === OtpCode::RESET_PASSWORD ? 'password reset' : 'verification') . ' code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp', with: [
            'code' => $this->code,
            'name' => $this->name,
            'reset' => $this->purpose === OtpCode::RESET_PASSWORD,
            'minutes' => OtpCode::TTL_MINUTES,
        ]);
    }
}
