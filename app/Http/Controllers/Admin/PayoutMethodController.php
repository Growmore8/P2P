<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Currency;
use App\Models\PayoutMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** "Receive methods": the ways clients can be paid out on sell orders, per currency / country. */
class PayoutMethodController extends Controller
{
    public function index()
    {
        return view('admin.payout-methods', [
            'methods' => PayoutMethod::orderBy('currency')->orderBy('sort')->get(),
            'currencies' => Currency::orderBy('sort')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $m = PayoutMethod::create($this->validated($request));
        ActivityLog::log('payout_method.created', "Added receive method {$m->title} ({$m->currency})", $m);

        return back()->with('success', 'Receive method added.');
    }

    public function update(Request $request, PayoutMethod $payoutMethod)
    {
        $payoutMethod->update($this->validated($request));
        ActivityLog::log('payout_method.updated', "Updated receive method {$payoutMethod->title} ({$payoutMethod->currency})", $payoutMethod);

        return back()->with('success', 'Receive method saved.');
    }

    public function destroy(PayoutMethod $payoutMethod)
    {
        ActivityLog::log('payout_method.deleted', "Deleted receive method {$payoutMethod->title} ({$payoutMethod->currency})", $payoutMethod);
        $payoutMethod->delete();

        return back()->with('success', 'Receive method deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'currency' => ['required', 'exists:currencies,code'],
            'type' => ['required', Rule::in(array_keys(PayoutMethod::TYPES))],
            'title' => ['required', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:255'],
            'countries' => ['nullable', 'array'],
            'countries.*' => [Rule::in(array_keys(config('countries')))],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['countries'] = array_values($data['countries'] ?? []) ?: null;
        $data['is_active'] = $request->boolean('is_active');
        $data['sort'] = $data['sort'] ?? 0;

        return $data;
    }
}
