<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Network;
use App\Models\NetworkWallet;
use Illuminate\Http\Request;

/** Your USDT deposit addresses — several per network allowed. */
class WalletController extends Controller
{
    public function store(Request $request)
    {
        $code = strtoupper((string) $request->input('code'));
        $request->validate(['code' => ['required', 'in:' . implode(',', array_keys(config('networks')))]], ['code.in' => 'Choose TRC20, BEP20 or ERC20.']);

        // The network row is created the first time an address is added for it.
        $preset = config("networks.{$code}");
        $network = Network::firstOrCreate(['code' => $code], [
            'name' => $preset['name'], 'fee' => $preset['fee'], 'address_regex' => $preset['regex'],
            'buy_enabled' => true, 'sell_enabled' => true, 'sort' => (int) Network::max('sort') + 1,
        ]);
        $address = $this->validAddress($request, $network);

        $network->wallets()->create(['address' => $address, 'is_active' => $request->boolean('active')]);
        ActivityLog::log('wallet.added', "Added {$code} address " . substr($address, 0, 6) . '…' . substr($address, -6));

        return redirect()->route('admin.networks.index')->with('success', "{$code} address added.");
    }

    public function update(Request $request, NetworkWallet $wallet)
    {
        $address = $this->validAddress($request, $wallet->network, $wallet);
        $wallet->update(['address' => $address, 'is_active' => $request->boolean('active')]);
        ActivityLog::log('wallet.updated', "Updated {$wallet->network->code} address " . substr($address, 0, 6) . '…' . substr($address, -6) . ($wallet->is_active ? '' : ' (inactive)'));

        return redirect()->route('admin.networks.index')->with('success', "{$wallet->network->code} address saved.");
    }

    public function destroy(NetworkWallet $wallet)
    {
        $code = $wallet->network->code;
        $wallet->delete(); // orders keep their own copy of the address
        ActivityLog::log('wallet.deleted', "Removed {$code} address " . substr($wallet->address, 0, 6) . '…');

        return back()->with('success', "{$code} address removed.");
    }

    /** Clean the pasted address and check it matches the network's format (a wrong address means lost USDT). */
    private function validAddress(Request $request, Network $network, ?NetworkWallet $wallet = null): string
    {
        $address = preg_replace('/\s+/', '', (string) $request->input('address'));
        $request->merge(['address' => $address, '_wallet' => $wallet?->id ?? 'new']);
        $request->validate(['address' => ['required', 'string', 'max:120']], ['address.required' => 'Paste the wallet address.']);

        if (! $network->validAddress($address)) {
            back()->withErrors(['address' => "This doesn't look like a valid {$network->code} address. Copy it again from your wallet app."])->withInput()->throwResponse();
        }
        $dupe = $network->wallets()->where('address', $address)->when($wallet, fn ($q) => $q->whereKeyNot($wallet->id))->exists();
        if ($dupe) {
            back()->withErrors(['address' => "This {$network->code} address is already added."])->withInput()->throwResponse();
        }

        return $address;
    }
}
