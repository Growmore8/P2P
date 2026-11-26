<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Order;
use App\Services\Pricing;

class PageController extends Controller
{
    public function home()
    {
        // Real, anonymised recent completed orders (no names, no exact amounts in INR).
        $activity = Order::with('user:id,country')->where('status', Order::COMPLETED)
            ->latest('completed_at')->limit(3)->get(['id', 'user_id', 'side', 'usdt_amount', 'completed_at']);

        return view('pages.home', ['pricing' => Pricing::config(), 'activity' => $activity]);
    }

    public function p2p()
    {
        return view('pages.p2p', ['pricing' => Pricing::config(), 'networks' => Network::ordered()->get()]);
    }

    public function page(string $view)
    {
        abort_unless(in_array($view, ['services', 'about', 'insights', 'contact', 'legal'], true), 404);

        return view('pages.' . $view);
    }
}
