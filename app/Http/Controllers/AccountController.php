<?php

namespace App\Http\Controllers;

use App\Services\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        return view('user.account', ['user' => $request->user()]);
    }

    public function limits(Request $request)
    {
        $user = $request->user();
        $currency = $user->currency();
        $code = $user->currencyCode();

        return view('user.limits', [
            'user' => $user,
            'kyc' => $user->kyc,
            'currency' => $currency,
            'code' => $code,
            'steps' => $user->verificationSteps(),
            'level' => $user->effectiveKycLevel(),
            'limits' => $currency ? [
                'buy' => ['used' => Pricing::monthlyUsed($user, 'buy', $code), 'limit' => Pricing::monthlyLimit($currency, 'buy', $user)],
                'sell' => ['used' => Pricing::monthlyUsed($user, 'sell', $code), 'limit' => Pricing::monthlyLimit($currency, 'sell', $user)],
            ] : null,
        ]);
    }

    public function security(Request $request)
    {
        return view('user.security', ['user' => $request->user()]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.different' => 'The new password must be different from the current one.',
        ]);

        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();

        return redirect()->route('account.security')->with('success', 'Password changed successfully.');
    }
}
