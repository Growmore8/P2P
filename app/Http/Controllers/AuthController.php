<?php

namespace App\Http\Controllers;

use App\Models\Consent;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Onramp-style auth: one "Email or Mobile" step, then either
 * the password step (existing account) or a short sign-up step (new account).
 */
class AuthController extends Controller
{
    /** Step 1 — "Sign up or login". */
    public function showLogin(Request $request)
    {
        return view('auth.start', ['country' => old('country', $request->session()->get('auth.country', 'IN'))]);
    }

    public function identify(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:150'],
            'country' => ['required', 'in:' . implode(',', array_keys(config('countries')))],
        ]);
        // No checkbox here: existing clients already agreed, new clients agree on the sign-up step.

        $id = trim($data['identifier']);
        if (str_contains($id, '@')) {
            if (! filter_var($id, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages(['identifier' => 'Enter a valid email address.']);
            }
            $type = 'email';
            $value = Str::lower($id);
        } else {
            $value = Phone::normalize($data['country'], $id);
            if (! $value) {
                throw ValidationException::withMessages(['identifier' => Phone::error($data['country'])]);
            }
            $type = 'phone';
        }

        $account = User::where($type, $value)->first(['id', 'is_admin']);
        $exists = (bool) $account;
        $request->session()->put('auth', [
            'type' => $type, 'value' => $value, 'country' => $data['country'],
            'admin' => (bool) $account?->is_admin, // password step shows the Admin login screen
        ]);

        return redirect()->route($exists ? 'login.password' : 'register');
    }

    /** Step 2a — password for an existing account. */
    public function showPassword(Request $request)
    {
        $auth = $request->session()->get('auth');
        if (! $auth) {
            return redirect()->route('login');
        }

        return view('auth.password', ['auth' => $auth]);
    }

    public function login(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);
        $auth = $request->session()->get('auth');
        // "email" is still accepted directly (classic form / API compatibility).
        $field = $auth['type'] ?? 'email';
        $value = $auth['value'] ?? Str::lower((string) $request->input('email'));
        if (! $value) {
            return redirect()->route('login');
        }

        $key = $value . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'password' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.',
            ]);
        }

        if (! Auth::attempt([$field => $value, 'password' => $request->input('password')], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['password' => 'Incorrect password. Please try again.']);
        }

        RateLimiter::clear($key);
        $request->session()->forget('auth');
        $request->session()->regenerate();
        $user = $request->user();

        if ($user->isBlocked()) {
            Auth::logout();
            throw ValidationException::withMessages(['password' => 'Your account has been suspended. Please contact support.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        if ($user->is_admin) {
            \App\Models\ActivityLog::log('admin.login', "Admin {$user->email} logged in", $user);
        }

        return redirect()->intended($user->is_admin ? route('admin.dashboard') : route('dashboard'));
    }

    /** Step 2b — short sign-up for a new account. */
    public function showRegister(Request $request)
    {
        return view('auth.register', ['auth' => $request->session()->get('auth')]);
    }

    public function register(Request $request)
    {
        // Normalise before the unique check so "Ravi@Mail.com" and "ravi@mail.com" are the same account.
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $countries = config('countries');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'country' => ['required', 'in:' . implode(',', array_keys($countries))],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
            'risk' => ['accepted'],
            'age' => ['accepted'],
        ], [
            'risk.accepted' => 'Please confirm you have read the Risk Disclosure.',
            'age.accepted' => 'You must be 18 or older to open an account.',
            'country.in' => 'Please choose your country.',
            'email.unique' => 'An account with this email already exists. Please log in.',
            'terms.accepted' => 'You must accept the terms to continue.',
        ]);

        $data['email'] = Str::lower($data['email']);
        $data['phone'] = Phone::normalize($data['country'], $data['phone'])
            ?? throw ValidationException::withMessages(['phone' => Phone::error($data['country'])]);
        if (User::where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages(['phone' => 'This mobile number is already registered. Please log in.']);
        }

        $user = User::create($data);
        Consent::record($user, Consent::ACCOUNT_TYPES, $request);
        $request->session()->forget('auth');
        Auth::login($user);
        $request->session()->regenerate();

        \App\Models\OtpCode::send($user->email, \App\Models\OtpCode::VERIFY_EMAIL, $user->name);

        return redirect()->route('verification.notice')->with('success', 'Account created! Enter the code we emailed you.');
    }

    public function restart(Request $request)
    {
        $request->session()->forget('auth');

        return redirect()->route('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
