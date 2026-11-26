<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\PayoutMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('user.accounts', [
            'accounts' => $user->bankAccounts()->with('method')->latest()->get(),
            'methods' => PayoutMethod::forUser($user),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $methods = PayoutMethod::forUser($user);
        $method = $methods->firstWhere('id', (int) $request->input('payout_method_id'));
        if (! $method) {
            throw ValidationException::withMessages(['payout_method_id' => 'Choose how you want to receive money.']);
        }

        $india = $user->isIndian();
        $rules = ['holder_name' => ['required', 'string', 'max:100']];
        $rules += match ($method->type) {
            'upi' => ['upi_id' => ['required', 'regex:/^[\w.\-]{2,256}@[a-zA-Z]{2,64}$/']],
            'wallet' => [
                'bank_name' => ['required', 'string', 'max:60'],
                'account_number' => ['required', 'regex:/^\+?[0-9 ]{6,20}$/', 'confirmed'],
            ],
            default => [
                'bank_name' => ['required', 'string', 'max:100'],
                'account_number' => ['required', $india ? 'regex:/^\d{9,18}$/' : 'regex:/^[A-Za-z0-9 \-]{5,34}$/', 'confirmed'],
                'ifsc' => ['required', $india ? 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/' : 'regex:/^[A-Za-z0-9 \-]{3,20}$/'],
            ],
        };
        $data = $request->validate($rules, [
            'account_number.regex' => $method->type === 'wallet' ? 'Enter a valid wallet number.' : 'Enter a valid account number / IBAN.',
            'ifsc.regex' => $india ? 'Enter a valid IFSC code.' : 'Enter a valid SWIFT / branch code.',
            'upi_id.regex' => 'Enter a valid UPI ID (e.g. name@okbank).',
        ]);

        if ($user->bankAccounts()->count() >= 5) {
            return back()->with('error', 'You can save up to 5 payout accounts.');
        }

        $user->bankAccounts()->create([
            'type' => $method->type,
            'currency' => $method->currency,
            'payout_method_id' => $method->id,
            'holder_name' => $data['holder_name'],
            'upi_id' => $data['upi_id'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'account_number' => isset($data['account_number']) ? strtoupper(str_replace(' ', '', $data['account_number'])) : null,
            'ifsc' => isset($data['ifsc']) ? strtoupper(str_replace(' ', '', $data['ifsc'])) : null,
        ]);

        return back()->with('success', 'Payout account added.');
    }

    public function destroy(Request $request, BankAccount $account)
    {
        abort_unless($account->user_id === $request->user()->id, 403);
        $account->delete();

        return back()->with('success', 'Payout account removed.');
    }
}
