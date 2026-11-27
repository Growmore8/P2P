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
            'name' => ['nullable', 'string', 'max:60'],
            'symbol' => ['nullable', 'string', 'max:8'],
            'buy_rate' => ['required', 'numeric', 'gt:0'],
            'sell_rate' => ['required', 'numeric', 'gt:0', 'lte:buy_rate'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['required', 'numeric', 'min:0'],
            'tds_percent' => ['required', 'numeric', 'min:0', 'max:30'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ], ['sell_rate.lte' => 'Sell rate should not be higher than the buy rate.']);
        $data['is_active'] = $request->boolean('is_active');
        // Name and symbol come from config/currency_meta.php — no need to type them.
        $code = $currency?->code ?? $data['code'];
        $meta = config('currency_meta.' . $code);
        foreach (['name' => 0, 'symbol' => 1] as $field => $i) {
            if (blank($data[$field] ?? null)) {
                if ($currency) {
                    unset($data[$field]);
                } else {
                    $data[$field] = $meta[$i] ?? ($i ? $code . ' ' : $code);
                }
            }
        }
        if ($currency && ! isset($data['sort'])) {
            unset($data['sort']);
        } else {
            $data['sort'] = $data['sort'] ?? Currency::max('sort') + 1;
        }

        return $data;
    }
}
