<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Network extends Model
{
    protected $fillable = ['code', 'name', 'fee', 'deposit_address', 'address_regex', 'buy_enabled', 'sell_enabled', 'sort'];

    protected function casts(): array
    {
        return ['buy_enabled' => 'boolean', 'sell_enabled' => 'boolean', 'fee' => 'decimal:2'];
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort')->orderBy('id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(NetworkWallet::class)->orderBy('id');
    }

    /**
     * The deposit address for a new sell order: active addresses take turns (least recently used first).
     * Each order keeps a copy of the address it was given.
     */
    public function nextWalletAddress(): ?string
    {
        return DB::transaction(function () {
            $w = $this->wallets()->where('is_active', true)->reorder()
                ->orderByRaw('last_used_at is not null')->orderBy('last_used_at')->orderBy('id')
                ->lockForUpdate()->first();
            if (! $w) {
                return null;
            }
            $w->forceFill(['last_used_at' => now()])->saveQuietly();

            return $w->address;
        });
    }

    /** networks.deposit_address mirrors the first active address (null = no active address → selling off). */
    public function syncDepositAddress(): void
    {
        $first = $this->wallets()->where('is_active', true)->value('address');
        if ($this->deposit_address !== $first) {
            $this->forceFill(['deposit_address' => $first])->saveQuietly();
        }
    }

    public function validAddress(string $address): bool
    {
        return ! $this->address_regex || (bool) @preg_match('/' . $this->address_regex . '/', $address);
    }
}
