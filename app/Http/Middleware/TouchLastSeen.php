<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Records when a signed-in user was last active (at most once a minute). */
class TouchLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && (! $user->last_seen_at || $user->last_seen_at->lt(now()->subMinute()))) {
            $user->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
