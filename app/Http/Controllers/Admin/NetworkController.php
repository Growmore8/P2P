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
        $network->delete();

        return back()->with('success', 'Network removed.');
    }

    private function validated(Request $request, ?Network $network = null): array
    {
        $data = $request->validate([
            'code' => $network ? ['prohibited'] : ['required', 'string', 'max:20', 'alpha_num', 'unique:networks,code'],
            'name' => ['required', 'string', 'max:60'],
            'fee' => ['required', 'numeric', 'min:0', 'max:1000'],
            'deposit_address' => ['nullable', 'string', 'max:120'],
            'address_regex' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! empty($data['address_regex']) && @preg_match('/' . $data['address_regex'] . '/', '') === false) {
            back()->withErrors(['address_regex' => 'Invalid regular expression.'])->throwResponse();
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
