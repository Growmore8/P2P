<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Admin's own account: name / email / password. Every change needs the current password. */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.profile', ['u' => $request->user()]);
    }

    public function update(Request $request)
    {
        $u = $request->user();
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($u->id)],
            'current_password' => ['required', 'current_password'],
        ], ['current_password.current_password' => 'Your current password is incorrect.']);

        $old = $u->email;
        $u->forceFill(['name' => $data['name'], 'email' => $data['email']])->save();
        ActivityLog::log('admin.profile', $old !== $u->email ? "Admin email changed from {$old} to {$u->email}" : 'Admin name updated', $u);

        return back()->with('success', $old !== $u->email
            ? "Saved. Use {$u->email} to log in from now on (password resets go there too)."
            : 'Profile saved.');
    }

    public function password(Request $request)
    {
        $u = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(10)->letters()->mixedCase()->numbers()],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.different' => 'The new password must be different from the current one.',
        ]);

        $u->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
        // sign out other devices that used the old password
        Auth::logoutOtherDevices($data['password']);
        ActivityLog::log('admin.password', "Admin {$u->email} changed their password", $u);

        return back()->with('success', 'Password changed. Other devices have been signed out.');
    }
}
