<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One of your USDT deposit addresses on a network. */
class NetworkWallet extends Model
{
    protected $fillable = ['network_id', 'address', 'is_active', 'last_used_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_used_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Keep networks.deposit_address = "an active address exists" (used by status checks and the public page).
        $sync = fn (NetworkWallet $w) => $w->network?->syncDepositAddress();
        static::saved($sync);
        static::deleted($sync);
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }
}
