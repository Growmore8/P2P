<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Currency extends Model
{
    protected $fillable = [
        'code', 'name', 'symbol', 'buy_rate', 'sell_rate', 'min_amount', 'max_amount',
        'monthly_buy_limit', 'monthly_sell_limit', 'tds_percent', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'buy_rate' => 'float', 'sell_rate' => 'float', 'min_amount' => 'float', 'max_amount' => 'float',
            'monthly_buy_limit' => 'float', 'monthly_sell_limit' => 'float', 'tds_percent' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('currency-symbols'));
        static::deleted(fn () => Cache::forget('currency-symbols'));
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort')->orderBy('code');
    }

    public static function symbolFor(?string $code): string
    {
        $map = Cache::rememberForever('currency-symbols', fn () => static::pluck('symbol', 'code')->all());

        return $map[$code] ?? ($code ? $code . ' ' : '');
    }

    /** Countries (ISO codes) whose local currency is this one. */
    public function countryCodes(): array
    {
        return array_keys(array_filter(config('countries'), fn ($c) => ($c[2] ?? null) === $this->code));
    }

    public function toPricing(): array
    {
        return [
            'code' => $this->code, 'symbol' => $this->symbol, 'name' => $this->name,
            'buy_rate' => $this->buy_rate, 'sell_rate' => $this->sell_rate,
            'min' => $this->min_amount, 'max' => $this->max_amount, 'tds' => $this->tds_percent,
        ];
    }
}
