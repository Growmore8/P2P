<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** New order-chat message. to_client → the client · to_staff → the desk inbox. */
class OrderChatMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public OrderMessage $msg, public string $type)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->type === 'to_client'
            ? "New message on your order {$this->order->order_no}"
            : "Client message on order {$this->order->order_no}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-chat', with: [
            'o' => $this->order,
            'msg' => $this->msg,
            'toClient' => $this->type === 'to_client',
            'url' => $this->type === 'to_client' ? route('orders.show', $this->order) : route('admin.orders.show', $this->order),
        ]);
    }
}
