<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycSubmission;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public const PERIODS = ['today' => 'Today', '7d' => '7 days', '30d' => '30 days', 'all' => 'All time'];

    public function index(Request $request)
    {
        Order::where('status', Order::PENDING)->where('expires_at', '<', now())->get()->each->expireIfDue();

        $period = array_key_exists($request->query('period'), self::PERIODS) ? $request->query('period') : '30d';
        $from = match ($period) {
            'today' => today(),
            '7d' => now()->subDays(6)->startOfDay(),
            '30d' => now()->subDays(29)->startOfDay(),
            default => null,
        };

        $clients = User::clients();
        $completed = Order::where('status', Order::COMPLETED)->when($from, fn ($q) => $q->where('completed_at', '>=', $from));

        // Per-currency money totals for the period (deposits received on buys, payouts sent on sells).
        $byCurrency = (clone $completed)
            ->select('currency', 'side', DB::raw('count(*) as orders'), DB::raw('sum(fiat_amount) as fiat'),
                DB::raw('sum(payout_fiat) as payout'), DB::raw('sum(usdt_amount) as usdt'))
            ->groupBy('currency', 'side')->get()
            ->groupBy('currency')->map(fn ($rows) => [
                'buy' => $rows->firstWhere('side', 'buy'),
                'sell' => $rows->firstWhere('side', 'sell'),
            ]);

        $usdt = (clone $completed)->select('side', DB::raw('sum(usdt_amount) as usdt'), DB::raw('count(*) as orders'))
            ->groupBy('side')->get()->keyBy('side');

        // Daily USDT volume, last 14 days (completed orders), for the chart.
        $start = now()->subDays(13)->startOfDay();
        $daily = Order::where('status', Order::COMPLETED)->where('completed_at', '>=', $start)
            ->get(['side', 'usdt_amount', 'completed_at'])
            ->groupBy(fn ($o) => $o->completed_at->format('Y-m-d'));
        $chart = collect(range(0, 13))->map(function ($i) use ($start, $daily) {
            $d = $start->copy()->addDays($i);
            $rows = $daily->get($d->format('Y-m-d'), collect());

            return [
                'label' => $d->format('d M'),
                'short' => $d->format('d'),
                'buy' => round((float) $rows->where('side', 'buy')->sum('usdt_amount'), 2),
                'sell' => round((float) $rows->where('side', 'sell')->sum('usdt_amount'), 2),
            ];
        });

        // Clients by country (top 8) with verified count and completed volume.
        $countries = User::clients()
            ->select('country', DB::raw('count(*) as clients'), DB::raw("sum(case when kyc_status = 'approved' then 1 else 0 end) as verified"))
            ->groupBy('country')->orderByDesc('clients')->limit(8)->get();
        $volByCountry = Order::where('orders.status', Order::COMPLETED)
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->select('users.country', DB::raw('sum(orders.usdt_amount) as usdt'))
            ->groupBy('users.country')->pluck('usdt', 'country');

        return view('admin.dashboard', [
            'period' => $period,
            'stats' => [
                'clients' => (clone $clients)->count(),
                'new_clients' => (clone $clients)->when($from, fn ($q) => $q->where('created_at', '>=', $from))->count(),
                'verified' => (clone $clients)->where('kyc_status', 'approved')->count(),
                'pending_kyc' => KycSubmission::where('status', 'pending')->count(),
                'to_verify' => Order::where('status', Order::SUBMITTED)->count(),
                'processing' => Order::where('status', Order::PROCESSING)->count(),
                'awaiting' => Order::where('status', Order::PENDING)->count(),
                'buy_usdt' => (float) ($usdt['buy']->usdt ?? 0),
                'sell_usdt' => (float) ($usdt['sell']->usdt ?? 0),
                'buy_orders' => (int) ($usdt['buy']->orders ?? 0),
                'sell_orders' => (int) ($usdt['sell']->orders ?? 0),
            ],
            'byCurrency' => $byCurrency,
            'chart' => $chart,
            'countries' => $countries,
            'volByCountry' => $volByCountry,
            'queue' => Order::with('user')->whereIn('status', [Order::SUBMITTED, Order::PROCESSING])->oldest('paid_at')->limit(8)->get(),
            'kycQueue' => KycSubmission::with('user')->where('status', 'pending')->oldest()->limit(5)->get(),
        ]);
    }
}
