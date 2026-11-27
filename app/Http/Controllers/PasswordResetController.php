<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/** Forgot password: email → 6-digit code → new password. */
class PasswordResetController extends Controller
{
    private const SESSION = 'pwreset';

    public function showForgot(Request $request)
    {
        $auth = $request->session()->get('auth');

        return view('auth.forgot', ['email' => old('email', ($auth['type'] ?? null) === 'email' ? $auth['value'] : '')]);
    }

    public function sendCode(Request $request)
    {
        $email = Str::lower(trim($request->validate(['email' => ['required', 'email']])['email']));
        $user = User::where('email', $email)->first();

        if ($user) {
            if ($wait = OtpCode::cooldown($email, OtpCode::RESET_PASSWORD)) {
                return back()->withInput()->withErrors(['email' => "Please wait {$wait} seconds before requesting a new code."]);
            }
            OtpCode::send($email, OtpCode::RESET_PASSWORD, $user->name);
        }
        // Same response whether or not the account exists (no account fishing).
        $request->session()->put(self::SESSION, ['email' => $email, 'verified' => false]);

        return redirect()->route('password.code')->with('success', 'If an account exists for ' . $email . ', a code has been sent.');
    }

    public function showCode(Request $request)
    {
        $s = $request->session()->get(self::SESSION);
        if (! $s) {
            return redirect()->route('password.forgot');
        }

        return view('auth.otp', [
            'title' => 'Enter reset code',
            'intro' => 'Enter the 6-digit reset code sent to',
            'email' => $s['email'],
            'action' => route('password.code.verify'),
            'resend' => route('password.code.resend'),
            'change' => route('password.forgot'),
            'cooldown' => OtpCode::cooldown($s['email'], OtpCode::RESET_PASSWORD),
            'devCode' => session('dev_otp.' . OtpCode::RESET_PASSWORD),
        ]);
    }

    public function verifyCode(Request $request)
    {
        $s = $request->session()->get(self::SESSION);
        if (! $s) {
            return redirect()->route('password.forgot');
        }
        $request->validate(['code' => ['required', 'digits:6']], ['code.digits' => 'Enter the 6-digit code.']);

        $result = OtpCode::check($s['email'], OtpCode::RESET_PASSWORD, $request->input('code'));
        if ($result !== true) {
            return back()->withErrors(['code' => $result]);
        }
        $request->session()->put(self::SESSION, ['email' => $s['email'], 'verified' => true, 'at' => now()->timestamp]);

        return redirect()->route('password.new');
    }

    public function resend(Request $request)
    {
        $s = $request->session()->get(self::SESSION);
        if (! $s) {
            return redirect()->route('password.forgot');
        }
        if ($wait = OtpCode::cooldown($s['email'], OtpCode::RESET_PASSWORD)) {
            return back()->withErrors(['code' => "Please wait {$wait} seconds before requesting a new code."]);
        }
        if ($user = User::where('email', $s['email'])->first()) {
            OtpCode::send($s['email'], OtpCode::RESET_PASSWORD, $user->name);
        }

        return back()->with('success', 'A new code has been sent.');
    }

    public function showNew(Request $request)
    {
        if (! $this->verifiedEmail($request)) {
            return redirect()->route('password.forgot');
        }

        return view('auth.reset', ['email' => $this->verifiedEmail($request)]);
    }

    public function update(Request $request)
    {
        $email = $this->verifiedEmail($request);
        if (! $email) {
            return redirect()->route('password.forgot')->withErrors(['email' => 'Your reset session expired. Please start again.']);
        }
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()]]);

        $user = User::where('email', $email)->firstOrFail();
        $user->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
        if ($user->is_admin) {
            ActivityLog::create(['actor_id' => $user->id, 'action' => 'admin.password_reset', 'subject_type' => 'User', 'subject_id' => $user->id,
                'description' => "Admin {$user->email} reset their password by email code", 'ip' => $request->ip()]);
        }
        $request->session()->forget([self::SESSION, 'auth']);

        return redirect()->route('login')->with('success', 'Password changed. Please log in with your new password.');
    }

    /** Email whose code was verified in the last 15 minutes, or null. */
    private function verifiedEmail(Request $request): ?string
    {
        $s = $request->session()->get(self::SESSION);

        return ($s['verified'] ?? false) && now()->timestamp - ($s['at'] ?? 0) < 900 ? $s['email'] : null;
    }
}
