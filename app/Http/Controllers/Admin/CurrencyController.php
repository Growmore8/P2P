<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Currency;
use App\Models\PayoutMethod;
use App\Services\MarketRate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function index()
    {
        $used = collect(config('countries'))->pluck(2)->unique()->sort()->values();

        return view('admin.currencies', [
            'currencies' => Currency::orderByDesc('is_active')->orderBy('sort')->orderBy('code')->get(),
            'available' => $used->diff(Currency::pluck('code'))->values(),
            'orderCounts' => \App\Models\Order::selectRaw('currency, count(*) as n')->groupBy('currency')->pluck('n', 'currency'),
        ]);
    }

    /** Live market price + suggested rates + usual payment methods for a currency (used by the Add / Edit forms). */
    public function suggest(string $code)
    {
        $code = strtoupper($code);
        abort_unless(in_array($code, collect(config('countries'))->pluck(2)->all(), true), 404);

        return response()->json(['code' => $code, 'rates' => MarketRate::suggest($code), 'presets' => MarketRate::presets($code)]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $c = Currency::create($data);

        // Add the usual "receive" account types for this country (e.g. LKR → Bank transfer, eZ Cash). Editable later.
        $added = [];
        if ($request->boolean('add_receive')) {
            foreach (MarketRate::presets($c->code)['receive'] as $i => [$type, $title]) {
                PayoutMethod::firstOrCreate(['currency' => $c->code, 'title' => $title], ['type' => $type, 'is_active' => true, 'sort' => $i]);
                $added[] = $title;
            }
        }
        ActivityLog::log('currency.created', "Added currency {$c->code} (buy {$c->buy_rate}, sell {$c->sell_rate})" . ($added ? ' · receive methods: ' . implode(', ', $added) : ''), $c);

        return redirect()->route('admin.currencies.index')
            ->with('success', "{$c->code} added." . ($added ? ' Receive methods added: ' . implode(', ', $added) . '.' : ''))
            ->with('suggest_pay', $c->code);
    }

    public function update(Request $request, Currency $currency)
    {
        $old = "{$currency->buy_rate}/{$currency->sell_rate}";
        $currency->update($this->validated($request, $currency));
        ActivityLog::log('currency.updated', "Updated {$currency->code}: rates {$old} → {$currency->buy_rate}/{$currency->sell_rate}" . ($currency->is_active ? '' : ' (disabled)'), $currency);

        return back()->with('success', "{$currency->code} saved.");
    }

    /**
     * Delete a currency that was never traded (with its payment methods).
     * INR is the base for everyone's limits; currencies with orders are kept for records — switch them off instead.
     */
    public function destroy(Currency $currency)
    {
        if ($currency->isBase()) {
            return back()->with('error', "{$currency->code} is the main currency (all limits are based on it) and can't be deleted.");
        }
        if (\App\Models\Order::where('currency', $currency->code)->exists()) {
            return back()->with('error', "{$currency->code} has orders, so it must be kept for records. Untick Active to switch it off instead.");
        }

        $code = $currency->code;
        \Illuminate\Support\Facades\DB::transaction(function () use ($currency, $code) {
            \App\Models\DepositMethod::where('currency', $code)->delete();
            \App\Models\PayoutMethod::where('currency', $code)->delete();
            $currency->delete();
        });
        ActivityLog::log('currency.deleted', "Deleted currency {$code} and its payment methods");

        return redirect()->route('admin.currencies.index')->with('success', "{$code} deleted.");
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
