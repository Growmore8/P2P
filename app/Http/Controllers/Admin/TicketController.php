<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TicketMail;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        $tickets = Ticket::with(['user:id,name,email,country', 'order:id,order_no'])
            ->withCount('messages')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->query('category'), fn ($q, $c) => $q->where('category', $c))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('ticket_no', 'like', "%{$s}%")->orWhere('subject', 'like', "%{$s}%")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))
                ->orWhereHas('order', fn ($o) => $o->where('order_no', 'like', "%{$s}%"))))
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->latest('last_reply_at')->paginate(25)->withQueryString();

        $counts = Ticket::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('admin.tickets.index', ['tickets' => $tickets, 'status' => $status, 'counts' => $counts]);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load('messages.author', 'user.kyc', 'order');
        $others = Ticket::where('user_id', $ticket->user_id)->where('id', '!=', $ticket->id)->latest()->limit(5)->get();

        return view('admin.tickets.show', ['ticket' => $ticket, 'others' => $others]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'status' => ['required', Rule::in([Ticket::ANSWERED, Ticket::RESOLVED, Ticket::CLOSED])],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        $path = $request->hasFile('attachment') ? $request->file('attachment')->store("tickets/{$ticket->user_id}", 'local') : null;

        $msg = $ticket->messages()->create(['user_id' => $request->user()->id, 'is_staff' => true, 'body' => $data['message'], 'attachment' => $path]);
        $ticket->update([
            'status' => $data['status'],
            'last_reply_at' => now(),
            'closed_at' => $data['status'] === Ticket::CLOSED ? now() : null,
        ]);

        $sent = Notify::mail($ticket->user->email, new TicketMail($ticket->load('user', 'order'), 'reply', $msg));
        ActivityLog::log('ticket.reply', "Replied to {$ticket->ticket_no} ({$ticket->statusLabel()})", $ticket);

        return back()->with($sent ? 'success' : 'error', $sent
            ? "Reply sent and emailed to {$ticket->user->email}."
            : 'Reply saved, but the email could not be sent — check the mail settings.');
    }

    public function status(Request $request, Ticket $ticket)
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Ticket::STATUSES))]]);
        $ticket->update(['status' => $data['status'], 'closed_at' => $data['status'] === Ticket::CLOSED ? now() : null]);
        ActivityLog::log('ticket.status', "{$ticket->ticket_no} → {$ticket->statusLabel()}", $ticket);

        return back()->with('success', "Ticket marked {$ticket->statusLabel()}.");
    }
}
