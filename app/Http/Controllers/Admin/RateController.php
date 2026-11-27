<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Currency;
use Illuminate\Http\Request;

/** Quick buy / sell rate changes — used by managers (and admins) when the admin is busy. */
class RateController extends Controller
{
    public function index()
    {
        return view('admin.rates', [
            'currencies' => Currency::with('ratesUpdater')->where('is_active', true)->orderBy('sort')->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, Currency $currency)
    {
        abort_unless($currency->is_active, 404);
        $data = $request->validate([
            'buy_rate' => ['required', 'numeric', 'gt:0'],
            'sell_rate' => ['required', 'numeric', 'gt:0', 'lte:buy_rate'],
        ], ['sell_rate.lte' => 'Sell rate should not be higher than the buy rate.']);

        $old = "{$currency->buy_rate} / {$currency->sell_rate}";
        $currency->forceFill($data + ['rates_updated_by' => $request->user()->id, 'rates_updated_at' => now()])->save();
        $who = ($request->user()->is_admin ? 'Admin' : 'Manager') . ' ' . $request->user()->name;
        ActivityLog::log('currency.rates', "{$who} changed {$currency->code} rates: {$old} → {$currency->buy_rate} / {$currency->sell_rate}", $currency);

        return back()->with('success', "{$currency->code} rates saved: buy {$currency->symbol}" . number_format($currency->buy_rate, 2) . ", sell {$currency->symbol}" . number_format($currency->sell_rate, 2) . '.');
    }
}
