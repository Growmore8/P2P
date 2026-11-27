<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        Order::where('status', Order::PENDING)->where('expires_at', '<', now())->get()->each->expireIfDue();

        $orders = Order::with(['user', 'reviewer'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('review') === 'waiting', fn ($q) => $q->where('status', Order::SUBMITTED)->whereNull('review_status'))
            ->when($request->query('review') === 'reviewed', fn ($q) => $q->where('review_status', 'received')->whereIn('status', Order::OPEN))
            ->when($request->query('side'), fn ($q, $s) => $q->where('side', $s))
            ->when($request->query('currency'), fn ($q, $c) => $q->where('currency', $c))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('order_no', 'like', "%{$s}%")->orWhere('user_reference', 'like', "%{$s}%")->orWhere('admin_reference', 'like', "%{$s}%")
                ->orWhere('wallet_address', 'like', "%{$s}%")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"))))
            ->latest()->paginate(25)->withQueryString();

        $counts = Order::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $counts['review_waiting'] = Order::where('status', Order::SUBMITTED)->whereNull('review_status')->count();
        $counts['review_done'] = Order::where('review_status', 'received')->whereIn('status', Order::OPEN)->count();

        return view('admin.orders.index', ['orders' => $orders, 'counts' => $counts]);
    }

    public function show(Order $order)
    {
        $order->expireIfDue();
        $order->load('user.kyc', 'logs.actor');
        $dupRef = $order->user_reference
            ? Order::where('user_reference', $order->user_reference)->where('id', '!=', $order->id)->get(['order_no', 'status'])
            : collect();

        return view('admin.orders.show', ['order' => $order, 'dupRef' => $dupRef]);
    }

    /** Payment / USDT received and verified → processing. */
    /**
     * Manager (or admin) step: confirm whether the client's payment / USDT actually arrived.
     * The admin then approves (accept / complete) or rejects the order.
     */
    public function review(Request $request, Order $order)
    {
        $data = $request->validate([
            'result' => ['required', 'in:received,not_received'],
            'review_note' => ['nullable', 'string', 'max:255', 'required_if:result,not_received'],
        ], ['review_note.required_if' => 'Add a short note about what is missing.']);

        if (! in_array($order->status, [Order::SUBMITTED, Order::PROCESSING], true)) {
            return back()->with('error', 'Only orders waiting for verification can be reviewed.');
        }
        $received = $data['result'] === 'received';
        $note = trim((string) ($data['review_note'] ?? ''));
        $order->update(['review_status' => $data['result'], 'review_note' => $note ?: null, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $who = ($request->user()->is_admin ? 'Admin' : 'Manager') . ' ' . $request->user()->name;
        $what = $order->isBuy() ? 'payment' : 'USDT';
        $order->logs()->create(['status' => $order->status, 'actor_id' => $request->user()->id,
            'note' => "{$who} reviewed: {$what} " . ($received ? 'received' : 'NOT received') . ($note ? " — {$note}" : '')]);
        ActivityLog::log('order.reviewed', "Order {$order->order_no} reviewed by {$who}: " . ($received ? 'received' : 'not received'), $order);

        return back()->with('success', $received ? "Marked as reviewed — {$what} received. The admin can now approve it." : "Marked as {$what} not received. The admin has been told.");
    }

    public function accept(Request $request, Order $order)
    {
        $request->validate(['note' => ['nullable', 'string', 'max:255']]);

        return $this->move($request, $order, [Order::SUBMITTED], Order::PROCESSING,
            $request->input('note') ?: ($order->isBuy() ? 'INR payment verified' : 'USDT receipt verified'));
    }

    /** USDT sent (buy) or INR paid out (sell) → completed. */
    public function complete(Request $request, Order $order)
    {
        $data = $request->validate([
            'admin_reference' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['admin_reference.required' => $order->isBuy() ? 'Enter the USDT transaction hash.' : 'Enter the payout UTR.']);

        return $this->move($request, $order, [Order::SUBMITTED, Order::PROCESSING], Order::COMPLETED,
            ($data['note'] ?? null) ?: ($order->isBuy() ? 'USDT sent · tx ' : 'INR paid · UTR ') . $data['admin_reference'],
            ['admin_reference' => $data['admin_reference'], 'completed_at' => now()]);
    }

    public function reject(Request $request, Order $order)
    {
        $data = $request->validate(['reject_reason' => ['required', 'string', 'max:255']]);

        return $this->move($request, $order, Order::OPEN, Order::REJECTED, $data['reject_reason'], ['reject_reason' => $data['reject_reason']]);
    }

    private function move(Request $request, Order $order, array $from, string $to, ?string $note, array $extra = [])
    {
        $ok = DB::transaction(function () use ($request, $order, $from, $to, $note, $extra) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();
            if (! in_array($locked->status, $from, true)) {
                return false;
            }
            $locked->transition($to, $request->user()->id, $note, $extra);
            ActivityLog::log('order.' . $to, "Order {$locked->order_no} → " . Order::LABELS[$to] . ($note ? " ({$note})" : ''), $locked);

            return true;
        });

        // Let the client know when the order is finished either way.
        if ($ok && in_array($to, [Order::COMPLETED, Order::REJECTED], true)) {
            $fresh = $order->fresh('user');
            \App\Support\Notify::mail($fresh->user->email, new \App\Mail\OrderStatusMail($fresh));
        }

        return $ok
            ? back()->with('success', "Order {$order->order_no} → " . Order::LABELS[$to])
            : back()->with('error', 'Order status changed in the meantime. Refresh and try again.');
    }
}
