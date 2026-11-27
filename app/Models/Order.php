<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const PENDING = 'pending_payment';
    public const SUBMITTED = 'payment_submitted';
    public const PROCESSING = 'processing';
    public const COMPLETED = 'completed';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';
    public const EXPIRED = 'expired';

    public const LABELS = [
        self::PENDING => 'Awaiting payment',
        self::SUBMITTED => 'Payment submitted',
        self::PROCESSING => 'Processing',
        self::COMPLETED => 'Completed',
        self::REJECTED => 'Rejected',
        self::CANCELLED => 'Cancelled',
        self::EXPIRED => 'Auto-cancelled',
    ];

    public const OPEN = [self::PENDING, self::SUBMITTED, self::PROCESSING];

    protected $fillable = [
        'order_no', 'user_id', 'side', 'network', 'currency', 'rate', 'fiat_amount', 'usdt_amount', 'fee_usdt', 'tds_amount',
        'payout_fiat', 'status', 'wallet_address', 'deposit_method_id', 'deposit_snapshot', 'deposit_address',
        'payout_snapshot', 'user_reference', 'user_proof', 'admin_reference', 'reject_reason', 'expires_at',
        'paid_at', 'completed_at', 'review_status', 'review_note', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'deposit_snapshot' => 'array',
            'payout_snapshot' => 'array',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'rate' => 'decimal:2',
            'fiat_amount' => 'decimal:2',
            'usdt_amount' => 'decimal:2',
            'fee_usdt' => 'decimal:2',
            'tds_amount' => 'decimal:2',
            'payout_fiat' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_no';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Manager review: payment checked as received / not received (null = waiting). */
    public function reviewLabel(): string
    {
        return match ($this->review_status) {
            'received' => 'Reviewed — payment received',
            'not_received' => 'Payment not received',
            default => 'Waiting for review',
        };
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class)->orderBy('id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class)->orderBy('id');
    }

    /** Format an amount in this order's fiat currency. */
    public function money($amount, int $decimals = 2): string
    {
        return money($amount, $this->currency, $decimals);
    }

    /** Fiat the client pays (buy) or receives after TDS (sell). */
    public function clientFiat(): float
    {
        return (float) ($this->isBuy() ? $this->fiat_amount : $this->payout_fiat);
    }

    public function isBuy(): bool
    {
        return $this->side === 'buy';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function statusLabel(): string
    {
        return self::LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            self::COMPLETED => 'ok',
            self::REJECTED, self::CANCELLED, self::EXPIRED => 'bad',
            self::PROCESSING, self::SUBMITTED => 'info',
            default => 'warn',
        };
    }

    public static function explorerUrl(string $network, ?string $hash): ?string
    {
        if (! $hash || ! preg_match('/^(0x)?[A-Fa-f0-9]{40,80}$/', $hash)) {
            return null;
        }
        return match (strtoupper($network)) {
            'TRC20' => 'https://tronscan.org/#/transaction/' . $hash,
            'BEP20' => 'https://bscscan.com/tx/' . $hash,
            'ERC20' => 'https://etherscan.io/tx/' . $hash,
            'POLYGON' => 'https://polygonscan.com/tx/' . $hash,
            default => null,
        };
    }

    /** Index of progress step: 0 created, 1 paid, 2 verified, 3 completed. */
    public function step(): int
    {
        return match ($this->status) {
            self::SUBMITTED => 1,
            self::PROCESSING => 2,
            self::COMPLETED => 3,
            self::REJECTED => $this->paid_at ? 1 : 0,
            default => 0,
        };
    }

    /** Lazily expire unpaid orders whose payment window has passed. */
    public function expireIfDue(): bool
    {
        if ($this->status === self::PENDING && $this->expires_at && $this->expires_at->isPast()) {
            $this->transition(self::EXPIRED, null, 'Auto-cancelled: not paid within ' . Setting::get('order_expiry_minutes', 15) . ' minutes');
            return true;
        }
        return false;
    }

    public function transition(string $status, ?int $actorId = null, ?string $note = null, array $extra = []): void
    {
        $this->fill(array_merge($extra, ['status' => $status]))->save();
        $this->logs()->create(['status' => $status, 'actor_id' => $actorId, 'note' => $note]);
    }
}
