<?php

use App\Models\Order;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('orders:expire', function () {
    $n = 0;
    Order::where('status', Order::PENDING)->where('expires_at', '<', now())->get()
        ->each(function (Order $o) use (&$n) { $n += (int) $o->expireIfDue(); });
    $this->info("Expired {$n} order(s).");
})->purpose('Expire unpaid P2P orders past their payment window');

Schedule::command('orders:expire')->everyMinute();
