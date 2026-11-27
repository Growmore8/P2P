<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Network;
use Illuminate\Http\Request;

class NetworkController extends Controller
{
    public function index()
    {
        return view('admin.networks', ['networks' => Network::ordered()->get()]);
    }

    public function store(Request $request)
    {
        Network::create($this->validated($request));

        return back()->with('success', 'Network added.');
    }

    public function update(Request $request, Network $network)
    {
        $network->update($this->validated($request, $network));

        return back()->with('success', "{$network->code} updated.");
    }

    public function destroy(Network $network)
    {
        if (\App\Models\Order::where('network', $network->code)->whereIn('status', \App\Models\Order::OPEN)->exists()) {
            return back()->with('error', "{$network->code} has orders in progress. Finish them first, or untick Buy and Sell to hide it.");
        }
        $network->delete();

        return back()->with('success', 'Network removed.');
    }

    private function validated(Request $request, ?Network $network = null): array
    {
        // Addresses are pasted from wallet apps — strip spaces / line breaks.
        $request->merge(['deposit_address' => preg_replace('/\s+/', '', (string) $request->input('deposit_address')) ?: null]);
        $data = $request->validate([
            'code' => $network ? ['prohibited'] : ['required', 'string', 'max:20', 'alpha_num', 'unique:networks,code'],
            'name' => ['required', 'string', 'max:60'],
            'fee' => ['required', 'numeric', 'min:0', 'max:1000'],
            'deposit_address' => ['nullable', 'string', 'max:120'],
            'address_regex' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! empty($data['address_regex']) && @preg_match('/' . $data['address_regex'] . '/', '') === false) {
            back()->withErrors(['address_regex' => 'Invalid regular expression.'])->withInput()->throwResponse();
        }
        // A wrong deposit address means lost USDT — check it against the network's address format.
        $regex = $data['address_regex'] ?? $network?->address_regex;
        if (! empty($data['deposit_address']) && $regex && ! preg_match('/' . $regex . '/', $data['deposit_address'])) {
            $code = $network->code ?? strtoupper((string) ($data['code'] ?? ''));
            back()->withErrors(['deposit_address' => "This doesn't look like a valid {$code} address. Copy it again from your wallet app."])->withInput()->throwResponse();
        }
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }
        $data['sort'] = $data['sort'] ?? 0;
        $data['buy_enabled'] = $request->boolean('buy_enabled');
        $data['sell_enabled'] = $request->boolean('sell_enabled');

        return $data;
    }
}
