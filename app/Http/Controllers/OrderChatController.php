<?php

namespace App\Http\Controllers;

use App\Mail\OrderChatMail;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderMessage;
use App\Models\Setting;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Binance-style order chat: automatic status messages (from the order log) mixed with
 * messages between the client and the SRM desk. Used by both the client and the admin pages.
 */
class OrderChatController extends Controller
{
    public function feed(Request $request, Order $order)
    {
        $staff = $this->authorizeAccess($request, $order);

        return response()->json(['status' => $order->status, 'items' => $this->items($order, $staff)]);
    }

    public function send(Request $request, Order $order)
    {
        $staff = $this->authorizeAccess($request, $order);
        // Clients can chat only while the order is in progress; after that they raise a ticket.
        if (! $staff && ! $order->isOpen()) {
            return response()->json(['message' => 'This order is closed. Please raise a support ticket for help.'], 422);
        }
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:1000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], ['body.required_without' => 'Type a message or attach a file.']);

        $msg = $order->messages()->create([
            'user_id' => $request->user()->id,
            'is_staff' => $staff,
            'body' => isset($data['body']) ? trim($data['body']) : null,
            'attachment' => $request->hasFile('attachment') ? $request->file('attachment')->store("order-chat/{$order->id}", 'local') : null,
        ]);

        // Email the other side, at most once every 10 minutes per order, so a busy chat doesn't flood inboxes.
        $recent = $order->messages()->where('is_staff', $staff)->where('id', '<', $msg->id)->where('created_at', '>=', now()->subMinutes(10))->exists();
        if (! $recent) {
            $mail = new OrderChatMail($order, $msg, $staff ? 'to_client' : 'to_staff');
            $staff ? Notify::mail($order->user->email, $mail) : Notify::staff($mail);
        }

        return response()->json(['ok' => true, 'status' => $order->status, 'items' => $this->items($order->fresh(), $staff)]);
    }

    public function file(Request $request, OrderMessage $message)
    {
        $this->authorizeAccess($request, $message->order);
        abort_unless($message->attachment && Storage::disk('local')->exists($message->attachment), 404);

        return response()->file(Storage::disk('local')->path($message->attachment), ['Cache-Control' => 'private, max-age=600']);
    }

    /** true = the SRM desk (admin) is viewing; false = the order's own client. */
    private function authorizeAccess(Request $request, Order $order): bool
    {
        $user = $request->user();
        $staff = $user->isStaff() && $request->routeIs('admin.*');
        abort_unless($staff || $order->user_id === $user->id, 404);

        return $staff;
    }

    private function items(Order $order, bool $staff): array
    {
        $order->loadMissing('user');
        $items = [];

        $proof = $order->user_proof ? $this->proofUrl($order) : null;
        $proofPdf = $order->user_proof && str_ends_with(strtolower($order->user_proof), '.pdf');
        foreach ($order->logs()->get() as $log) {
            $withProof = $log->status === Order::SUBMITTED && $proof;
            $items[] = [
                'key' => 'l' . $log->id,
                'kind' => 'system',
                'body' => $this->systemText($order, $log, $staff),
                'image' => $withProof && ! $proofPdf ? $proof : null,
                'file' => $withProof && $proofPdf ? $proof : null,
                'time' => $log->created_at?->format('d M, h:i A'),
                'ts' => $log->created_at?->getTimestamp() ?? 0,
            ];
        }

        foreach ($order->messages()->get() as $m) {
            $mine = $m->is_staff === $staff;
            $file = $m->attachment ? route($staff ? 'admin.orders.chat.file' : 'orders.chat.file', $m) : null;
            $items[] = [
                'key' => 'm' . $m->id,
                'kind' => $mine ? 'me' : 'them',
                'name' => $m->is_staff ? 'SRM Fintech' : $order->user->name,
                'body' => $m->body,
                'image' => $file && ! str_ends_with(strtolower($m->attachment), '.pdf') ? $file : null,
                'file' => $file && str_ends_with(strtolower($m->attachment), '.pdf') ? $file : null,
                'time' => $m->created_at?->format('d M, h:i A'),
                'ts' => $m->created_at?->getTimestamp() ?? 0,
            ];
        }

        usort($items, fn ($a, $b) => [$a['ts'], $a['key'][0] === 'l' ? 0 : 1] <=> [$b['ts'], $b['key'][0] === 'l' ? 0 : 1]);

        return $items;
    }

    private function proofUrl(Order $order): string
    {
        return route('files.proof', $order);
    }

    private function systemText(Order $order, OrderLog $log, bool $staff): string
    {
        $buy = $order->isBuy();
        $usdt = number_format((float) $order->usdt_amount, 2) . ' USDT';
        $client = $order->user->name;
        $mins = (int) Setting::get('order_expiry_minutes', 15);

        $text = match ($log->status) {
            Order::PENDING => $buy
                ? ($staff ? "Order created. Waiting for {$client} to pay " . $order->money($order->fiat_amount) . '.'
                          : 'Order created. Pay ' . $order->money($order->fiat_amount) . " within {$mins} minutes using the payment details shown, then submit your payment reference and slip.")
                : ($staff ? "Order created. Waiting for {$client} to send {$usdt}."
                          : "You are selling {$usdt}. Send it to the address shown, then submit the transaction hash. You'll receive " . $order->money($order->payout_fiat) . ' after we confirm it.'),
            Order::SUBMITTED => $buy
                ? ($staff ? "{$client} has marked the order as paid (ref {$order->user_reference}). Please verify the payment in your account before releasing USDT."
                          : "You marked the order as paid (ref {$order->user_reference}). We're verifying the payment in our account before releasing your USDT.")
                : ($staff ? "{$client} has marked the USDT as sent (tx {$order->user_reference}). Please confirm it on-chain before paying out."
                          : 'You marked the USDT as sent. Waiting for us to confirm it on-chain and pay you.'),
            Order::PROCESSING => $buy
                ? ($staff ? 'Payment verified. Sending USDT.' : 'Payment verified. Your USDT is being sent.')
                : ($staff ? 'USDT confirmed. Sending the payout.' : 'USDT received. Your payout of ' . $order->money($order->payout_fiat) . ' is being sent.'),
            Order::COMPLETED => $buy
                ? ($staff ? 'You have released the USDT. The order is complete.' : "{$usdt} has been released to your wallet. The order is complete.")
                : ($staff ? 'Payout sent. The order is complete.' : 'Your payout has been sent. The order is complete.'),
            Order::REJECTED => 'The order was rejected.',
            Order::CANCELLED => 'The order was cancelled.',
            Order::EXPIRED => 'The order was auto-cancelled because payment was not made in time.',
            default => Order::LABELS[$log->status] ?? $log->status,
        };

        // Show the reason / reference for the closing steps.
        if (in_array($log->status, [Order::COMPLETED, Order::REJECTED], true) && $log->note) {
            $text .= ' ' . $log->note;
        }

        return $text;
    }
}
