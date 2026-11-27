<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells the client their order was completed or rejected. */
class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
    }

    public function envelope(): Envelope
    {
        $done = $this->order->status === Order::COMPLETED;

        return new Envelope(subject: ($done ? 'Order completed' : 'Order update') . " · {$this->order->order_no}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-status', with: ['o' => $this->order, 'url' => route('orders.show', $this->order)]);
    }
}
