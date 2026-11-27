<?php

namespace App\Mail;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Live chat emails. reply → visitor gets the admin's reply · staff → team inbox alert. */
class ChatMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ChatConversation $chat, public ChatMessage $msg, public string $type)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->type === 'reply' ? 'SRM Fintech Support replied to your chat' : 'New chat message from ' . $this->chat->displayName(),
            replyTo: $this->type === 'reply'
                ? [new Address((string) Setting::get('ticket_email'), 'SRM Fintech Support')]
                : ($this->chat->contactEmail() ? [new Address($this->chat->contactEmail(), $this->chat->displayName())] : []),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.chat', with: [
            'c' => $this->chat,
            'msg' => $this->msg,
            'type' => $this->type,
            'url' => $this->type === 'reply' ? url('/') . '#chat' : route('admin.chats.show', $this->chat),
        ]);
    }
}
