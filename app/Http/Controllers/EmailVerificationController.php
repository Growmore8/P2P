<?php

namespace App\Http\Controllers;

use App\Models\OtpCode;
use Illuminate\Http\Request;

/** Email OTP after sign-up. */
class EmailVerificationController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return redirect()->route('kyc.show');
        }

        return view('auth.otp', [
            'title' => 'Verify your email',
            'intro' => 'Enter the 6-digit verification code sent to',
            'email' => $user->email,
            'action' => route('verification.verify'),
            'resend' => route('verification.resend'),
            'change' => null,
            'cooldown' => OtpCode::cooldown($user->email, OtpCode::VERIFY_EMAIL),
            'devCode' => session('dev_otp.' . OtpCode::VERIFY_EMAIL),
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']], ['code.digits' => 'Enter the 6-digit code.']);
        $user = $request->user();

        $result = OtpCode::check($user->email, OtpCode::VERIFY_EMAIL, $request->input('code'));
        if ($result !== true) {
            return back()->withErrors(['code' => $result]);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('kyc.show')->with('success', 'Email verified. Now complete your identity verification.');
    }

    public function resend(Request $request)
    {
        $user = $request->user();
        if ($wait = OtpCode::cooldown($user->email, OtpCode::VERIFY_EMAIL)) {
            return back()->withErrors(['code' => "Please wait {$wait} seconds before requesting a new code."]);
        }
        OtpCode::send($user->email, OtpCode::VERIFY_EMAIL, $user->name);

        return back()->with('success', 'A new code has been sent to ' . $user->email . '.');
    }
}
