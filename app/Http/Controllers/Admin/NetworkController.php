<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Network;
use Illuminate\Http\Request;

class NetworkController extends Controller
{
    public function index()
    {
        return view('admin.networks', ['networks' => Network::ordered()->with('wallets')->get()]);
    }

    public function store(Request $request)
    {
        // From the "Add wallet" pop-up: pick a known network → name, fee and address format are filled in.
        $code = strtoupper((string) $request->input('code'));
        if ($preset = config("networks.{$code}")) {
            $request->merge([
                'code' => $code,
                'name' => $request->input('name') ?: $preset['name'],
                'fee' => $request->input('fee') ?? $preset['fee'],
                'address_regex' => $request->input('address_regex') ?: $preset['regex'],
            ]);
        }
        if ($request->has('active')) {
            $request->merge(['buy_enabled' => $request->boolean('active'), 'sell_enabled' => $request->boolean('active')]);
        }
        $n = Network::create($this->validated($request));

        return redirect()->route('admin.networks.index')->with('success', "{$n->code} wallet added.");
    }

    public function update(Request $request, Network $network)
    {
        $data = $this->validated($request, $network);
        unset($data['deposit_address']); // addresses are managed as wallets (several per network)
        $network->update($data);
        $network->syncDepositAddress();

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
            'name' => [$network ? 'sometimes' : 'required', 'string', 'max:60'], // edit form keeps the existing name
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
        $data['sort'] = $data['sort'] ?? ($network ? $network->sort : (int) Network::max('sort') + 1);
        $data['buy_enabled'] = $request->boolean('buy_enabled');
        $data['sell_enabled'] = $request->boolean('sell_enabled');

        return $data;
    }
}
