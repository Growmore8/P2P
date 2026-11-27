<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Currency extends Model
{
    protected $fillable = [
        'code', 'name', 'symbol', 'buy_rate', 'sell_rate', 'min_amount', 'max_amount',
        'level_limits', 'tds_percent', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'buy_rate' => 'float', 'sell_rate' => 'float', 'min_amount' => 'float', 'max_amount' => 'float',
            'level_limits' => 'array', 'tds_percent' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('currency-symbols'));
        static::deleted(fn () => Cache::forget('currency-symbols'));
    }

    /** Monthly limit for a verification level (1–3) and side; 0 = no limit, level 0 = cannot trade. */
    public function limitFor(int $level, string $side): float
    {
        if ($level < 1) {
            return 0.0;
        }
        $row = $this->level_limits[(string) min($level, 3)] ?? null;

        return (float) ($row[$side === 'buy' ? 0 : 1] ?? 0);
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
