<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function validAddress(string $address): bool
    {
        return ! $this->address_regex || (bool) @preg_match('/' . $this->address_regex . '/', $address);
    }
}
