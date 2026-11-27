<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Clients must confirm their email (OTP) before using the account. */
class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && ! $user->isStaff() && ! $user->email_verified_at) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
