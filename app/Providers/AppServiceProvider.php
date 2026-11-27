<?php

namespace App\Providers;

use App\Models\KycSubmission;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFour();

        View::composer('*', function ($view) {
            static $settings = null;
            if ($settings === null) {
                $settings = Schema::hasTable('settings') ? Setting::values() : Setting::DEFAULTS;
            }
            $view->with('settings', $settings);
        });

        View::composer('layouts.admin', function ($view) {
            $view->with('badge', [
                'kyc' => KycSubmission::where('status', 'pending')->count() + \App\Models\KycUpgrade::where('status', 'pending')->count(),
                'orders' => Order::where('status', Order::SUBMITTED)->count(),
                'tickets' => \App\Models\Ticket::where('status', \App\Models\Ticket::OPEN)->count(),
            ]);
        });
    }
}
