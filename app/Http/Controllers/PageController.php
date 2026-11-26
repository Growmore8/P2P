<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Order;
use App\Services\Pricing;

class PageController extends Controller
{
    public function home()
    {
        // Real, anonymised recent completed orders (country + rounded USDT only — never names or fiat amounts).
        // The page shows 3 at a time and rotates through these.
        $activity = Order::with('user:id,country')->where('status', Order::COMPLETED)
            ->latest('completed_at')->limit(20)->get(['id', 'user_id', 'side', 'usdt_amount', 'completed_at'])
            ->map(fn ($o) => [
                'cc' => strtolower($o->user->country ?? 'in'),
                'country' => config('countries.' . ($o->user->country ?? 'IN') . '.0', 'India'),
                'verb' => $o->side === 'buy' ? 'bought' : 'sold',
                'usdt' => number_format((float) $o->usdt_amount, 0),
                'ago' => $o->completed_at?->diffForHumans() ?? '',
            ]);

        return view('pages.home', ['pricing' => Pricing::config(), 'activity' => $activity, 'strip' => \App\Support\BrandStrip::build()]);
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
