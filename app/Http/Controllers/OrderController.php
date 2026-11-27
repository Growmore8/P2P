<?php

namespace App\Http\Controllers;

use App\Models\Consent;
use App\Models\DepositMethod;
use App\Models\Network;
use App\Models\Order;
use App\Models\Setting;
use App\Services\Pricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        return view('user.trade', [
            'side' => $request->query('side') === 'sell' ? 'sell' : 'buy',
            'amount' => (float) $request->query('amount', 0) ?: null,
            'network' => $request->query('network'),
            'currency' => $currency = $user->currency(),
            'pricing' => Pricing::config($currency),
            'networks' => Network::ordered()->get(),
            'methods' => DepositMethod::forUser($user),
            'accounts' => $user->bankAccounts()->where('currency', $user->currencyCode())->latest()->get(),
            'openOrder' => $user->orders()->whereIn('status', Order::OPEN)->latest()->first(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! Setting::get('trading_enabled')) {
            return back()->withInput()->with('error', 'Trading is temporarily paused. Please try again later.');
        }

        $data = $request->validate([
            'side' => ['required', Rule::in(['buy', 'sell'])],
            'network' => ['required', 'exists:networks,code'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'wallet_address' => ['required_if:side,buy', 'nullable', 'string', 'max:120'],
            'deposit_method_id' => ['required_if:side,buy', 'nullable', 'integer'],
            'bank_account_id' => ['required_if:side,sell', 'nullable', 'integer'],
            'confirm' => ['accepted'],
        ], [
            'deposit_method_id.required_if' => 'Choose a payment method.',
            'bank_account_id.required_if' => 'Choose a payout account.',
            'confirm.accepted' => 'Please confirm the declaration.',
        ]);

        if ($user->orders()->whereIn('status', Order::OPEN)->count() >= 3) {
            return back()->withInput()->with('error', 'You have 3 open orders. Complete or cancel one before creating another.');
        }

        $currency = $user->currency();
        if (! $currency) {
            return back()->withInput()->with('error', 'Trading is not available in ' . $user->countryName() . ' yet. Please contact support.');
        }

        $network = Network::where('code', $data['network'])->firstOrFail();
        $side = $data['side'];
        if (($side === 'buy' && ! $network->buy_enabled) || ($side === 'sell' && ! $network->sell_enabled)) {
            throw ValidationException::withMessages(['network' => "{$network->name} is not available for {$side} orders right now."]);
        }

        $q = Pricing::quote($side, (float) $data['amount'], $network, $currency);
        if ($q['error']) {
            throw ValidationException::withMessages(['amount' => $q['error']]);
        }

        $limit = Pricing::monthlyLimit($currency, $side, $user);
        if ($limit > 0) {
            $remaining = $limit - Pricing::monthlyUsed($user, $side, $currency->code);
            if ($q['fiat_amount'] > $remaining) {
                throw ValidationException::withMessages(['amount' => 'This exceeds your monthly ' . $side . ' limit. Remaining this month: '
                    . money(max($remaining, 0), $currency->code) . '. Complete the next verification level to increase your limit.']);
            }
        }

        $attrs = [
            'side' => $side,
            'network' => $network->code,
            'currency' => $currency->code,
            'rate' => $q['rate'],
            'fiat_amount' => $q['fiat_amount'],
            'usdt_amount' => $q['usdt_amount'],
            'fee_usdt' => $q['fee_usdt'],
            'tds_amount' => $q['tds_amount'],
            'payout_fiat' => $q['payout_fiat'],
            'status' => Order::PENDING,
            'expires_at' => now()->addMinutes((int) Setting::get('order_expiry_minutes', 30)),
        ];

        if ($side === 'buy') {
            $wallet = trim($data['wallet_address']);
            if (! $network->validAddress($wallet)) {
                throw ValidationException::withMessages(['wallet_address' => "Enter a valid {$network->code} wallet address."]);
            }
            $method = DepositMethod::forUser($user)->firstWhere('id', (int) $data['deposit_method_id']);
            if (! $method || ! $method->supports($q['fiat_amount'])) {
                throw ValidationException::withMessages(['deposit_method_id' => 'This payment method is not available for this amount.']);
            }
            $attrs += ['wallet_address' => $wallet, 'deposit_method_id' => $method->id, 'deposit_snapshot' => $method->snapshot()];
        } else {
            if (! $network->deposit_address) {
                throw ValidationException::withMessages(['network' => 'Selling on this network is not available right now.']);
            }
            $account = $user->bankAccounts()->where('currency', $currency->code)->find($data['bank_account_id']);
            if (! $account) {
                throw ValidationException::withMessages(['bank_account_id' => 'Choose a valid payout account.']);
            }
            $attrs += ['deposit_address' => $network->deposit_address, 'payout_snapshot' => $account->snapshot()];
        }

        $order = DB::transaction(function () use ($user, $attrs) {
            do {
                $no = 'SRM' . now()->format('ymd') . strtoupper(Str::random(5));
            } while (Order::where('order_no', $no)->exists());

            $order = $user->orders()->create($attrs + ['order_no' => $no]);
            $order->logs()->create(['status' => Order::PENDING, 'actor_id' => $user->id, 'note' => 'Order created']);

            return $order;
        });
        Consent::record($user, ['p2p_order'], $request, $order->order_no);

        return redirect()->route('orders.show', $order)->with('success', 'Order created. Complete the payment within the time limit.');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $user->orders()->where('status', Order::PENDING)->where('expires_at', '<', now())->get()->each->expireIfDue();

        $orders = $user->orders()
            ->when($request->query('status'), fn ($q, $s) => match ($s) {
                'ongoing' => $q->whereIn('status', Order::OPEN),
                'closed' => $q->whereIn('status', [Order::REJECTED, Order::CANCELLED, Order::EXPIRED]),
                default => $q->where('status', $s),
            })
            ->when($request->query('side'), fn ($q, $s) => $q->where('side', $s))
            ->latest()->paginate(15)->withQueryString();

        return view('user.orders', ['orders' => $orders, 'ongoing' => $user->orders()->whereIn('status', Order::OPEN)->count()]);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeOwner($request, $order);
        $order->expireIfDue();

        return view('user.order', ['order' => $order->load('logs'), 'whatsapp' => Setting::get('support_whatsapp')]);
    }

    public function submitPayment(Request $request, Order $order)
    {
        $this->authorizeOwner($request, $order);
        $order->expireIfDue();

        $data = $request->validate([
            'user_reference' => $order->isBuy()
                ? ['required', 'string', 'regex:/^[A-Za-z0-9_\-]{6,40}$/']
                : ['required', 'string', 'regex:/^(0x)?[A-Fa-f0-9]{40,80}$/'],
            'user_proof' => [$order->isBuy() ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'user_reference.regex' => $order->isBuy() ? 'Enter a valid UTR / payment ID (letters, numbers, _ or -).' : 'Enter a valid transaction hash.',
        ]);

        $updated = DB::transaction(function () use ($request, $order, $data) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();
            if ($locked->status !== Order::PENDING) {
                return false;
            }
            $path = $request->hasFile('user_proof') ? $request->file('user_proof')->store("proofs/{$order->user_id}", 'local') : null;
            $locked->transition(Order::SUBMITTED, $request->user()->id, 'Customer submitted ' . ($locked->isBuy() ? 'UTR ' : 'tx hash ') . $data['user_reference'], [
                'user_reference' => $data['user_reference'],
                'user_proof' => $path,
                'paid_at' => now(),
            ]);

            return true;
        });

        return $updated
            ? back()->with('success', 'Payment details submitted. Our team will verify and process your order.')
            : back()->with('error', 'This order can no longer accept payment details.');
    }

    public function cancel(Request $request, Order $order)
    {
        $this->authorizeOwner($request, $order);
        if ($order->status !== Order::PENDING) {
            return back()->with('error', 'Only orders awaiting payment can be cancelled.');
        }
        $order->transition(Order::CANCELLED, $request->user()->id, 'Cancelled by customer');

        return back()->with('success', 'Order cancelled.');
    }

    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
    }
}
