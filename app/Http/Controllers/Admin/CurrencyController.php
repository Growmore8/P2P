<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function index()
    {
        $used = collect(config('countries'))->pluck(2)->unique()->sort()->values();

        return view('admin.currencies', [
            'currencies' => Currency::orderBy('sort')->orderBy('code')->get(),
            'available' => $used->diff(Currency::pluck('code'))->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $c = Currency::create($data);
        ActivityLog::log('currency.created', "Added currency {$c->code} (buy {$c->buy_rate}, sell {$c->sell_rate})", $c);

        return back()->with('success', "{$c->code} added.");
    }

    public function update(Request $request, Currency $currency)
    {
        $old = "{$currency->buy_rate}/{$currency->sell_rate}";
        $currency->update($this->validated($request, $currency));
        ActivityLog::log('currency.updated', "Updated {$currency->code}: rates {$old} → {$currency->buy_rate}/{$currency->sell_rate}" . ($currency->is_active ? '' : ' (disabled)'), $currency);

        return back()->with('success', "{$currency->code} saved.");
    }

    private function validated(Request $request, ?Currency $currency = null): array
    {
        $codes = collect(config('countries'))->pluck(2)->unique()->all();
        $data = $request->validate([
            'code' => $currency ? ['prohibited'] : ['required', Rule::in($codes), 'unique:currencies,code'],
            'name' => ['required', 'string', 'max:60'],
            'symbol' => ['required', 'string', 'max:8'],
            'buy_rate' => ['required', 'numeric', 'gt:0'],
            'sell_rate' => ['required', 'numeric', 'gt:0', 'lte:buy_rate'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['required', 'numeric', 'min:0'],
            'limits' => ['required', 'array'],
            'limits.*.buy' => ['required', 'numeric', 'min:0'],
            'limits.*.sell' => ['required', 'numeric', 'min:0'],
            'tds_percent' => ['required', 'numeric', 'min:0', 'max:30'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ], ['sell_rate.lte' => 'Sell rate should not be higher than the buy rate.']);
        // Monthly limits per verification level → {"1":[buy,sell],"2":[...],"3":[...]}
        $data['level_limits'] = collect([1, 2, 3])->mapWithKeys(fn ($l) => [(string) $l => [
            (float) ($data['limits'][$l]['buy'] ?? 0), (float) ($data['limits'][$l]['sell'] ?? 0),
        ]])->all();
        unset($data['limits']);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort'] = $data['sort'] ?? 0;

        return $data;
    }
}
