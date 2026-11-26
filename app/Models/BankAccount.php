<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A client's own account for RECEIVING money.
 * type upi    → upi_id
 * type bank   → bank_name, account_number, ifsc (IFSC / SWIFT / branch code)
 * type wallet → bank_name (wallet provider), account_number (wallet number)
 */
class BankAccount extends Model
{
    protected $fillable = ['user_id', 'type', 'currency', 'payout_method_id', 'holder_name', 'account_number', 'ifsc', 'bank_name', 'upi_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PayoutMethod::class, 'payout_method_id');
    }

    public function label(): string
    {
        return match ($this->type) {
            'upi' => 'UPI · ' . $this->upi_id,
            'wallet' => ($this->bank_name ?: 'Wallet') . ' · ' . $this->account_number,
            default => ($this->bank_name ?: 'Bank') . ' · XXXX' . substr((string) $this->account_number, -4),
        };
    }

    public function snapshot(): array
    {
        return $this->only(['type', 'currency', 'holder_name', 'account_number', 'ifsc', 'bank_name', 'upi_id']);
    }
}
