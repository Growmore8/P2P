<?php

namespace App\Http\Controllers;

use App\Mail\TicketMail;
use App\Models\Ticket;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Client support tickets. */
class SupportController extends Controller
{
    public function index(Request $request)
    {
        return view('user.support.index', [
            'tickets' => $request->user()->tickets()->latest('last_reply_at')->latest('id')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        return view('user.support.create', [
            'orders' => $user->orders()->latest()->limit(20)->get(['id', 'order_no', 'side', 'usdt_amount', 'status', 'created_at']),
            'category' => $request->query('category'),
            'orderNo' => $request->query('order'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(Ticket::CATEGORIES))],
            'order_id' => ['nullable', 'integer'],
            'subject' => ['required', 'string', 'min:4', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'category.required' => 'Choose what your issue is about.',
            'message.min' => 'Please describe the issue in a little more detail (at least 10 characters).',
        ]);

        if ($user->tickets()->whereIn('status', [Ticket::OPEN, Ticket::ANSWERED])->count() >= 5) {
            return back()->withInput()->with('error', 'You already have 5 open tickets. Please continue in one of them.');
        }

        $orderId = null;
        if (! empty($data['order_id'])) {
            $orderId = $user->orders()->whereKey($data['order_id'])->value('id'); // only the client's own orders
        }

        $path = $request->hasFile('attachment') ? $request->file('attachment')->store("tickets/{$user->id}", 'local') : null;

        $ticket = DB::transaction(function () use ($user, $data, $orderId, $path) {
            $t = Ticket::create([
                'ticket_no' => Ticket::newNumber(),
                'user_id' => $user->id,
                'order_id' => $orderId,
                'category' => $data['category'],
                'subject' => $data['subject'],
                'status' => Ticket::OPEN,
                'last_reply_at' => now(),
            ]);
            $t->messages()->create(['user_id' => $user->id, 'is_staff' => false, 'body' => $data['message'], 'attachment' => $path]);

            return $t;
        });

        $ticket->load('user', 'order');
        $first = $ticket->messages()->first();
        Notify::mail($user->email, new TicketMail($ticket, 'created'));
        Notify::staff(new TicketMail($ticket, 'staff_new', $first));

        return redirect()->route('support.show', $ticket)->with('ticket_created', true);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->own($request, $ticket);

        return view('user.support.show', ['ticket' => $ticket->load('messages.author', 'order')]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $this->own($request, $ticket);
        if ($ticket->isClosed()) {
            return back()->with('error', 'This ticket is closed. Please raise a new ticket.');
        }
        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        $path = $request->hasFile('attachment') ? $request->file('attachment')->store("tickets/{$ticket->user_id}", 'local') : null;

        $msg = $ticket->messages()->create(['user_id' => $request->user()->id, 'is_staff' => false, 'body' => $data['message'], 'attachment' => $path]);
        $ticket->update(['status' => Ticket::OPEN, 'last_reply_at' => now()]);
        Notify::staff(new TicketMail($ticket->load('user', 'order'), 'staff_reply', $msg));

        return back()->with('success', 'Reply sent. Our team will get back to you.');
    }

    public function close(Request $request, Ticket $ticket)
    {
        $this->own($request, $ticket);
        $ticket->update(['status' => Ticket::CLOSED, 'closed_at' => now()]);

        return back()->with('success', 'Ticket closed. Thank you!');
    }

    private function own(Request $request, Ticket $ticket): void
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);
    }
}
