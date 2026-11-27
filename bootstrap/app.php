<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'active' => \App\Http\Middleware\EnsureActive::class,
            'kyc' => \App\Http\Middleware\EnsureKycApproved::class,
            'consent' => \App\Http\Middleware\EnsureConsent::class,
            'email.verified' => \App\Http\Middleware\EnsureEmailVerified::class,
        ]);
        $middleware->web(append: [\App\Http\Middleware\TouchLastSeen::class]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(fn ($request) => $request->user()?->is_admin ? '/admin' : ($request->user()?->isManager() ? '/admin/orders' : '/dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
