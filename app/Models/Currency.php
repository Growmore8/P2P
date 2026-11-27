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

    /** Monthly limits are set once on this currency; every other currency follows by exchange rate. */
    public const BASE = 'INR';

    public function isBase(): bool
    {
        return $this->code === self::BASE;
    }

    public static function base(): ?Currency
    {
        return static::where('code', self::BASE)->first();
    }

    /** Monthly limit for a verification level (1–3) and side; 0 = no limit, level 0 = cannot trade. */
    public function limitFor(int $level, string $side): float
    {
        if ($level < 1) {
            return 0.0;
        }
        $base = $this->isBase() ? null : static::base();
        if ($base && $base->buy_rate > 0 && $this->buy_rate > 0) {
            $baseLimit = $base->limitFor($level, $side);

            return $baseLimit > 0 ? self::nice($baseLimit * $this->buy_rate / $base->buy_rate) : 0.0;
        }
        $row = config('limits.base.' . min($level, 3));

        return (float) ($row[$side === 'buy' ? 0 : 1] ?? 0);
    }

    /** Round to a clean figure: 160,427 → 160,000; 1,234 → 1,200; 87 → 87. */
    public static function nice(float $v): float
    {
        if ($v < 100) {
            return round($v);
        }
        $step = 10 ** (floor(log10($v)) - 1);

        return round($v / $step) * $step;
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
