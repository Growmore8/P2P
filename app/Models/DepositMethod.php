<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Where clients PAY fiat for buy orders (per currency / country). */
class DepositMethod extends Model
{
    public const TYPES = ['upi' => 'UPI', 'bank' => 'Bank transfer', 'wallet' => 'Mobile wallet', 'link' => 'Payment link', 'other' => 'Other'];

    protected $fillable = [
        'type', 'currency', 'countries', 'title', 'upi_id', 'pay_url', 'qr_image', 'account_holder', 'account_number', 'ifsc', 'bank_name',
        'instructions', 'min_amount', 'max_amount', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'countries' => 'array', 'min_amount' => 'decimal:2', 'max_amount' => 'decimal:2'];
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    /** Active methods for the user's currency and country. */
    public static function forUser(User $user)
    {
        return static::active()->where('currency', $user->currencyCode())->get()
            ->filter(fn ($m) => $m->availableIn($user->country))->values();
    }

    public function availableIn(string $country): bool
    {
        return empty($this->countries) || in_array($country, $this->countries, true);
    }

    public function supports(float $amount): bool
    {
        return $amount >= (float) $this->min_amount
            && ((float) $this->max_amount <= 0 || $amount <= (float) $this->max_amount);
    }

    public function countriesLabel(): string
    {
        return empty($this->countries) ? 'All ' . $this->currency . ' countries'
            : collect($this->countries)->map(fn ($c) => config("countries.$c.0", $c))->join(', ');
    }

    public function subtitle(): string
    {
        return match ($this->type) {
            'upi' => 'PhonePe, GPay, Paytm, etc.',
            'bank' => 'Pay via bank transfer',
            'wallet' => 'Pay from your mobile wallet',
            'link' => 'Pay online — UPI, card or netbanking',
            default => 'See instructions after placing the order',
        };
    }

    public function snapshot(): array
    {
        return $this->only(['type', 'currency', 'title', 'upi_id', 'pay_url', 'qr_image', 'account_holder', 'account_number', 'ifsc', 'bank_name', 'instructions']);
    }
}
