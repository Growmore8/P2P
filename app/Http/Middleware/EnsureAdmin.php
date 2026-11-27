<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    /** Pages a manager can use: see orders, mark them reviewed, the order chat, change rates, and their own account. */
    public const MANAGER_ROUTES = [
        'admin.orders.index', 'admin.orders.show', 'admin.orders.review',
        'admin.orders.chat', 'admin.orders.chat.send', 'admin.orders.chat.file',
        'admin.profile', 'admin.profile.update', 'admin.profile.password',
        'admin.rates', 'admin.rates.update', 'admin.currencies.suggest', // change buy / sell rates when the admin is busy
        'admin.users.index', 'admin.users.show', // client history, read-only, with phone / email masked
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user?->is_admin) {
            return $next($request);
        }
        if ($user?->isManager()) {
            if ($request->routeIs(self::MANAGER_ROUTES)) {
                return $next($request);
            }
            // e.g. the admin dashboard → send managers to their order list instead of an error
            return $request->isMethod('get') ? redirect()->route('admin.orders.index') : abort(403);
        }
        abort(403);
    }
}
