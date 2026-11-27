<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Order;
use App\Services\Pricing;

class PageController extends Controller
{
    public function home()
    {
        return view('pages.home', ['pricing' => Pricing::config(), 'activity' => self::activity(), 'strip' => \App\Support\BrandStrip::build(),
            'region' => self::region(request())]);
    }

    /** Visitor's detected country and whether we trade in its currency yet (for the region notice). */
    private static function region(\Illuminate\Http\Request $request): ?array
    {
        $cc = \App\Support\GeoCountry::detect($request);
        if (! $cc) {
            return null;
        }
        $cur = config("countries.$cc.2");

        return ['code' => $cc, 'name' => config("countries.$cc.0"), 'served' => \App\Models\Currency::where('code', $cur)->where('is_active', true)->exists()];
    }

    /** JSON for the live feed (polled by the homepage). */
    public function activityFeed()
    {
        return response()->json(self::activity())->header('Cache-Control', 'public, max-age=15');
    }

    /**
     * Latest REAL completed orders, newest first. Shows a masked nickname of the actual client
     * (e.g. "Ra*** K."), their country, rounded USDT and the real completion time — never fiat amounts.
     */
    private static function activity()
    {
        return Order::with('user:id,name,country')->where('status', Order::COMPLETED)->whereNotNull('completed_at')
            ->latest('completed_at')->limit(18)->get(['id', 'user_id', 'side', 'usdt_amount', 'completed_at'])
            ->map(fn ($o) => [
                'id' => $o->id,
                'nick' => self::maskName($o->user->name ?? ''),
                'cc' => strtolower($o->user->country ?? 'in'),
                'country' => config('countries.' . ($o->user->country ?? 'IN') . '.0', 'India'),
                'verb' => $o->side === 'buy' ? 'bought' : 'sold',
                'usdt' => number_format((float) $o->usdt_amount, 0),
                'ts' => $o->completed_at->toIso8601String(),
                'ago' => $o->completed_at->diffForHumans(),
            ])->values();
    }

    /** "Ravi Kumar" → "Ra*** K." · "Priya" → "Pr***" — enough to feel personal, not enough to identify. */
    private static function maskName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $first = $parts[0] ?? '';
        if ($first === '') {
            return 'A client';
        }
        $nick = mb_substr($first, 0, 2) . '***';
        if (count($parts) > 1) {
            $nick .= ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.';
        }

        return $nick;
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
