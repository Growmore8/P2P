<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\DepositMethod;
use App\Models\Network;
use App\Models\PayoutMethod;

/** One page that shows, per currency, whether clients can buy and sell — and what to fix. */
class PaymentSetupController extends Controller
{
    public function index()
    {
        $networks = Network::ordered()->get();
        $sellWallets = $networks->filter(fn ($n) => $n->sell_enabled && $n->deposit_address);
        $buyNetworks = $networks->where('buy_enabled', true);

        $rows = Currency::orderByDesc('is_active')->orderBy('sort')->orderBy('code')->get()->map(function (Currency $c) use ($sellWallets, $buyNetworks) {
            $deposit = DepositMethod::where('currency', $c->code)->get();
            $payout = PayoutMethod::where('currency', $c->code)->get();
            $depositOn = $deposit->where('is_active', true)->count();
            $payoutOn = $payout->where('is_active', true)->count();
            $ratesOk = $c->buy_rate > 0 && $c->sell_rate > 0 && $c->sell_rate <= $c->buy_rate;
            $limitsOk = collect($c->level_limits ?? [])->flatten()->filter(fn ($v) => (float) $v > 0)->isNotEmpty();

            $checks = [
                ['ok' => $ratesOk, 'label' => 'Buy & sell rates', 'detail' => $ratesOk ? "Buy {$c->symbol}" . number_format($c->buy_rate, 2) . " · Sell {$c->symbol}" . number_format($c->sell_rate, 2) : 'Set your rates', 'fix' => route('admin.currencies.index') . '#cur-' . $c->code],
                ['ok' => $limitsOk, 'label' => 'Monthly limits', 'detail' => $limitsOk ? 'Level 1 ' . money($c->limitFor(1, 'buy'), $c->code, 0) . ' → Level 3 ' . money($c->limitFor(3, 'buy'), $c->code, 0) : 'No limits set (unlimited)', 'fix' => route('admin.currencies.index') . '#cur-' . $c->code, 'optional' => true],
                ['ok' => $depositOn > 0, 'label' => 'How clients pay you (buy)', 'detail' => $depositOn ? "{$depositOn} active · " . $deposit->where('is_active', true)->pluck('title')->take(2)->join(', ') : ($deposit->count() ? $deposit->count() . ' added but switched off' : 'No deposit method yet'), 'fix' => route('admin.methods.index')],
                ['ok' => $payoutOn > 0, 'label' => 'How you pay clients (sell)', 'detail' => $payoutOn ? "{$payoutOn} active · " . $payout->where('is_active', true)->pluck('title')->take(2)->join(', ') : 'No receive method yet', 'fix' => route('admin.payout-methods.index')],
                ['ok' => $sellWallets->isNotEmpty(), 'label' => 'Your USDT wallet (for sell orders)', 'detail' => $sellWallets->isNotEmpty() ? $sellWallets->pluck('code')->join(', ') : 'No deposit address set', 'fix' => route('admin.networks.index')],
            ];

            return [
                'c' => $c,
                'checks' => $checks,
                'canBuy' => $c->is_active && $ratesOk && $depositOn > 0 && $buyNetworks->isNotEmpty(),
                'canSell' => $c->is_active && $ratesOk && $payoutOn > 0 && $sellWallets->isNotEmpty(),
                'countries' => count($c->countryCodes()),
            ];
        });

        return view('admin.payments', ['rows' => $rows, 'networks' => $networks, 'sellWallets' => $sellWallets]);
    }
}
