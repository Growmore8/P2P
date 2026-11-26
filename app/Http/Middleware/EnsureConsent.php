<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Clients must accept the current version of the Terms, Privacy Policy and Risk Disclosure. */
class EnsureConsent
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && ! $user->is_admin && ! $user->hasCurrentConsent()) {
            if ($request->expectsJson()) {
                abort(403, 'Please accept the updated terms.');
            }
            session()->put('url.intended', $request->fullUrl());

            return redirect()->route('consent.show');
        }

        return $next($request);
    }
}
