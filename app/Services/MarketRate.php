<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Live USDT price in a local currency, used to SUGGEST buy / sell rates (the admin always decides).
 * Source: CoinGecko (tether), falling back to a USD exchange rate (USDT ≈ 1 USD). Cached for 10 minutes.
 */
class MarketRate
{
    /** Suggested markup: clients buy a little above market and sell a little below. */
    public const BUY_MARKUP = 0.02;   // +2 %

    public const SELL_MARKDOWN = 0.01; // −1 %

    public static function usdt(string $code): ?float
    {
        $code = strtolower($code);

        return Cache::remember("market-usdt-{$code}", 600, function () use ($code) {
            try {
                $r = Http::timeout(6)->get('https://api.coingecko.com/api/v3/simple/price', ['ids' => 'tether', 'vs_currencies' => $code]);
                $v = $r->ok() ? ($r->json("tether.{$code}") ?? null) : null;
                if ($v) {
                    return (float) $v;
                }
            } catch (Throwable) {
            }
            try {
                $r = Http::timeout(6)->get('https://open.er-api.com/v6/latest/USD');
                $v = $r->ok() ? ($r->json('rates.' . strtoupper($code)) ?? null) : null;

                return $v ? (float) $v : null;
            } catch (Throwable) {
                return null;
            }
        });
    }

    /** @return array{market: float, buy: float, sell: float}|null */
    public static function suggest(string $code): ?array
    {
        $m = self::usdt($code);
        if (! $m) {
            return null;
        }
        $round = fn (float $v) => $v >= 100 ? round($v, 1) : round($v, 4);

        return ['market' => round($m, 4), 'buy' => $round($m * (1 + self::BUY_MARKUP)), 'sell' => $round($m * (1 - self::SELL_MARKDOWN))];
    }

    /** Usual payment methods for a currency (config/payment_presets.php). */
    public static function presets(string $code): array
    {
        return config('payment_presets.' . strtoupper($code)) ?? config('payment_presets.*');
    }
}
