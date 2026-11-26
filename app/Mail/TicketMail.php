<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Support ticket emails.
 *  created      → client: "Thank you, we received your ticket"
 *  reply        → client: support replied (includes the reply)
 *  staff_new    → support inbox: a client opened a ticket
 *  staff_reply  → support inbox: a client replied
 */
class TicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket, public string $type, public ?TicketMessage $msg = null)
    {
    }

    public function envelope(): Envelope
    {
        $no = $this->ticket->ticket_no;

        $toStaff = str_starts_with($this->type, 'staff');

        return new Envelope(
            subject: match ($this->type) {
                'created' => "We received your ticket {$no}",
                'reply' => "Re: [{$no}] {$this->ticket->subject}",
                'staff_new' => "New ticket {$no}: {$this->ticket->subject}",
                default => "Client replied on {$no}",
            },
            // Client emails: "Reply" goes to the support inbox. Staff emails: "Reply" goes to the client.
            replyTo: $toStaff
                ? [new Address($this->ticket->user->email, $this->ticket->user->name)]
                : [new Address((string) Setting::get('support_email'), 'SRM Fintech Support')],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.ticket', with: [
            't' => $this->ticket,
            'type' => $this->type,
            'msg' => $this->msg,
            'url' => str_starts_with($this->type, 'staff')
                ? route('admin.tickets.show', $this->ticket)
                : route('support.show', $this->ticket),
        ]);
    }
}
