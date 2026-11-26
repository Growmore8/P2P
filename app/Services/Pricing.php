<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Network;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;

/**
 * Server-side price calculation, per fiat currency. The browser calculator mirrors
 * this logic, but orders are ALWAYS priced here so the client can never set the rate.
 */
class Pricing
{
    /**
     * Data for the JS calculator. $currency = the user's currency (trade screen);
     * null = public widget, which lists every active currency (INR first).
     */
    public static function config(?Currency $currency = null): array
    {
        $currencies = $currency ? collect([$currency]) : Currency::active()->get();
        $default = $currencies->first();

        return [
            'trading_enabled' => (bool) Setting::get('trading_enabled'),
            'currency' => $default?->code,
            'currencies' => $currencies->mapWithKeys(fn ($c) => [$c->code => $c->toPricing()])->all(),
            'networks' => Network::ordered()->get(['code', 'name', 'fee', 'buy_enabled', 'sell_enabled'])
                ->map(fn ($n) => [
                    'code' => $n->code, 'name' => $n->name, 'fee' => (float) $n->fee,
                    'buy' => $n->buy_enabled, 'sell' => $n->sell_enabled,
                ])->values()->all(),
        ];
    }

    /** Monthly limit in the currency (0 = unlimited). */
    public static function monthlyLimit(Currency $currency, string $side): float
    {
        return $side === 'buy' ? $currency->monthly_buy_limit : $currency->monthly_sell_limit;
    }

    /** Value of this calendar month's orders (in the currency) that count toward the limit. */
    public static function monthlyUsed(User $user, string $side, string $currency): float
    {
        return (float) $user->orders()
            ->where('side', $side)
            ->where('currency', $currency)
            ->whereNotIn('status', [Order::CANCELLED, Order::EXPIRED, Order::REJECTED])
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('fiat_amount');
    }

    /**
     * buy:  $amount = fiat the client pays.   sell: $amount = USDT the client sells.
     *
     * @return array{error:?string, rate:float, fiat_amount:float, usdt_amount:float, fee_usdt:float, tds_amount:float, payout_fiat:float}
     */
    public static function quote(string $side, float $amount, Network $network, Currency $currency): array
    {
        $q = ['error' => null, 'rate' => 0, 'fiat_amount' => 0, 'usdt_amount' => 0, 'fee_usdt' => 0, 'tds_amount' => 0, 'payout_fiat' => 0];

        if ($side === 'buy') {
            $rate = $currency->buy_rate;
            $fee = (float) $network->fee;
            $usdt = floor(($amount / $rate - $fee) * 100) / 100;
            $q = array_merge($q, ['rate' => $rate, 'fiat_amount' => round($amount, 2), 'usdt_amount' => max($usdt, 0), 'fee_usdt' => $fee]);
            $gross = $amount;
            if ($usdt <= 0) {
                $q['error'] = 'Amount is too small to cover the network fee.';
            }
        } else {
            $rate = $currency->sell_rate;
            $gross = round($amount * $rate, 2);
            $tds = round($gross * $currency->tds_percent / 100, 2);
            $q = array_merge($q, ['rate' => $rate, 'fiat_amount' => $gross, 'usdt_amount' => round($amount, 2), 'tds_amount' => $tds, 'payout_fiat' => round($gross - $tds, 2)]);
        }

        if (! $q['error'] && $currency->min_amount > 0 && $gross < $currency->min_amount) {
            $q['error'] = 'Minimum order value is ' . money($currency->min_amount, $currency->code, 0) . '.';
        } elseif (! $q['error'] && $currency->max_amount > 0 && $gross > $currency->max_amount) {
            $q['error'] = 'Maximum order value is ' . money($currency->max_amount, $currency->code, 0) . '. Contact us for OTC.';
        }

        return $q;
    }
}
