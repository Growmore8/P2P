<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A way clients can RECEIVE money for sell orders (per currency / country). */
class PayoutMethod extends Model
{
    public const TYPES = ['upi' => 'UPI', 'bank' => 'Bank account', 'wallet' => 'Mobile wallet'];

    protected $fillable = ['currency', 'countries', 'type', 'title', 'instructions', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['countries' => 'array', 'is_active' => 'boolean'];
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    /** Active methods available to a user's country + currency. */
    public static function forUser(User $user)
    {
        return static::active()->where('currency', $user->currencyCode())
            ->get()->filter(fn ($m) => empty($m->countries) || in_array($user->country, $m->countries, true))->values();
    }

    public function countriesLabel(): string
    {
        return empty($this->countries) ? 'All ' . $this->currency . ' countries'
            : collect($this->countries)->map(fn ($c) => config("countries.$c.0", $c))->join(', ');
    }
}
