<?php

namespace App\Http\Controllers;

use App\Models\DepositMethod;
use App\Models\KycSubmission;
use App\Models\KycUpgrade;
use App\Models\Order;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Serves private uploads (KYC docs, payment proofs, QR codes) only to authorised users.
 * Nothing is stored in the public web root.
 */
class FileController extends Controller
{
    public function kyc(Request $request, KycSubmission $kyc, string $doc)
    {
        abort_unless((array_key_exists($doc, KycSubmission::DOCS) || $doc === 'video') && $kyc->{$doc}, 404);
        abort_unless($request->user()->is_admin || $request->user()->id === $kyc->user_id, 403);

        return $this->serve($kyc->{$doc});
    }

    public function upgrade(Request $request, KycUpgrade $upgrade, string $key)
    {
        abort_unless($request->user()->is_admin || $request->user()->id === $upgrade->user_id, 403);
        abort_unless(is_string($upgrade->files[$key] ?? null), 404);

        return $this->serve($upgrade->files[$key]);
    }

    public function proof(Request $request, Order $order)
    {
        abort_unless($request->user()->isStaff() || $request->user()->id === $order->user_id, 403);

        return $this->serve($order->user_proof);
    }

    public function ticket(Request $request, TicketMessage $message)
    {
        abort_unless($request->user()->is_admin || $request->user()->id === $message->ticket->user_id, 403);

        return $this->serve($message->attachment);
    }

    public function qr(DepositMethod $method)
    {
        return $this->serve($method->qr_image);
    }

    public function orderQr(Request $request, Order $order)
    {
        abort_unless($request->user()->isStaff() || $request->user()->id === $order->user_id, 403);

        return $this->serve($order->deposit_snapshot['qr_image'] ?? null);
    }

    private function serve(?string $path)
    {
        abort_if(! $path || ! Storage::disk('local')->exists($path), 404);

        // BinaryFileResponse supports HTTP Range, so KYC videos can be seeked.
        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; media-src 'self'; img-src 'self'; style-src 'unsafe-inline'",
        ]);
    }
}
