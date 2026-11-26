<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureKycApproved
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->isKycApproved()) {
            return redirect()->route('kyc.show')->with('error', 'Please complete KYC verification before trading.');
        }

        return $next($request);
    }
}
