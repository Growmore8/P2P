<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    public const OPEN = 'open';          // waiting for support
    public const ANSWERED = 'answered';  // waiting for the client
    public const RESOLVED = 'resolved';
    public const CLOSED = 'closed';

    public const STATUSES = [
        self::OPEN => 'Open',
        self::ANSWERED => 'Answered',
        self::RESOLVED => 'Resolved',
        self::CLOSED => 'Closed',
    ];

    public const CATEGORIES = [
        'funds' => ['Funds not received', '💸', 'Paid but USDT / money not received'],
        'buy' => ['Buy order issue', '⇣', 'Problem with a buy order'],
        'sell' => ['Sell order issue', '⇡', 'Problem with a sell order'],
        'payment' => ['Payment issue', '💳', 'Wrong amount, failed or refund'],
        'kyc' => ['KYC / verification', '🛡', 'Documents, video or approval'],
        'account' => ['Account & login', '👤', 'Password, email, phone, security'],
        'other' => ['Something else', '💬', 'Any other question'],
    ];

    protected $fillable = ['ticket_no', 'user_id', 'order_id', 'category', 'subject', 'status', 'last_reply_at', 'closed_at'];

    protected function casts(): array
    {
        return ['last_reply_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'ticket_no';
    }

    public static function newNumber(): string
    {
        do {
            $no = 'TKT-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
        } while (static::where('ticket_no', $no)->exists());

        return $no;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('id');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category][0] ?? ucfirst($this->category);
    }

    public function categoryIcon(): string
    {
        return self::CATEGORIES[$this->category][1] ?? '💬';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            self::OPEN => 'warn',
            self::ANSWERED => 'info',
            self::RESOLVED => 'ok',
            default => 'bad',
        };
    }

    public function isClosed(): bool
    {
        return $this->status === self::CLOSED;
    }
}
