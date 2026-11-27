@extends('layouts.admin')
@section('title', 'Order ' . $order->order_no)
@section('heading', 'Order ' . $order->order_no)

@section('content')
@php
  $buy = $order->isBuy();
  $d = $order->deposit_snapshot ?? [];
  $p = $order->payout_snapshot ?? [];
  $u = $order->user;
@endphp

<a href="{{ route('admin.orders.index') }}" class="muted" style="font-size:14px">← Back to orders</a>

@if ($dupRef->isNotEmpty())
  <div class="alert alert-error" style="margin-top:14px">⚠ The same reference <span class="mono">{{ $order->user_reference }}</span> was used on other orders:
    @foreach ($dupRef as $x)<a class="green mono" href="{{ route('admin.orders.show', $x->order_no) }}">{{ $x->order_no }}</a> ({{ $x->status }}) @endforeach
  </div>
@endif
@if ($u->kyc_status !== 'approved')
  <div class="alert alert-error" style="margin-top:14px">⚠ Customer KYC status is <b>{{ $u->kyc_status }}</b>.</div>
@endif

<div class="split" style="margin-top:14px">
  <div>
    <div class="box">
      <div class="box-head">
        <div><span class="badge {{ $order->side }}">{{ strtoupper($order->side) }} USDT</span> <span class="badge {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span></div>
        <span class="muted" style="font-size:13px">Created {{ $order->created_at->format('d M Y, h:i A') }}</span>
      </div>
      <dl class="kv">
        <dt>Network</dt><dd>{{ $order->network }}</dd>
        <dt>Rate</dt><dd>{{ $order->money($order->rate) }}</dd>
        @if ($buy)
          <dt>Customer pays</dt><dd><b style="font-size:18px">{{ $order->money($order->fiat_amount) }}</b></dd>
          <dt>Send USDT</dt><dd><b style="font-size:18px" class="green">{{ number_format($order->usdt_amount, 2) }} USDT</b> <button class="copy" data-copy="{{ $order->usdt_amount }}">Copy</button> <span class="muted">(fee {{ $order->fee_usdt }} deducted)</span></dd>
          <dt>To wallet</dt><dd class="mono">{{ $order->wallet_address }} <button class="copy" data-copy="{{ $order->wallet_address }}">Copy</button></dd>
          <dt>Paid into</dt><dd>{{ $d['title'] ?? '—' }} · {{ ($d['type'] ?? '') === 'upi' ? ($d['upi_id'] ?? '') : ($d['bank_name'] ?? '') . ' ' . ($d['account_number'] ?? '') }}</dd>
        @else
          <dt>Customer sends</dt><dd><b style="font-size:18px">{{ number_format($order->usdt_amount, 2) }} USDT</b></dd>
          <dt>To our address</dt><dd class="mono">{{ $order->deposit_address }}</dd>
          <dt>Gross {{ $order->currency }}</dt><dd>{{ $order->money($order->fiat_amount) }}</dd>
          <dt>TDS</dt><dd>{{ $order->money($order->tds_amount) }}</dd>
          <dt>Pay out</dt><dd><b style="font-size:18px" class="green">{{ $order->money($order->payout_fiat) }}</b> <button class="copy" data-copy="{{ $order->payout_fiat }}">Copy</button></dd>
          <dt>Payout account</dt><dd>
            {{ $p['holder_name'] ?? '' }}<br>
            @if (($p['type'] ?? '') === 'upi')
              UPI: <span class="mono">{{ $p['upi_id'] }}</span> <button class="copy" data-copy="{{ $p['upi_id'] }}">Copy</button>
            @else
              {{ $p['bank_name'] ?? '' }} · <span class="mono">{{ $p['account_number'] ?? '' }}</span> <button class="copy" data-copy="{{ $p['account_number'] ?? '' }}">Copy</button>
              · IFSC <span class="mono">{{ $p['ifsc'] ?? '' }}</span> <button class="copy" data-copy="{{ $p['ifsc'] ?? '' }}">Copy</button>
            @endif
          </dd>
        @endif
        <dt>Expires</dt><dd>{{ $order->expires_at?->format('d M, h:i A') }}</dd>
      </dl>
    </div>

    <div class="box">
      <h3 style="margin-bottom:14px">Customer proof</h3>
      @if ($order->user_reference)
        <dl class="kv">
          <dt>{{ $buy ? 'UTR' : 'Tx hash' }}</dt>
          <dd class="mono">{{ $order->user_reference }} <button class="copy" data-copy="{{ $order->user_reference }}">Copy</button>
            @if (! $buy && ($x = \App\Models\Order::explorerUrl($order->network, $order->user_reference)))
              <a class="green" href="{{ $x }}" target="_blank" rel="noopener">Explorer ↗</a>
            @endif
          </dd>
          <dt>Submitted</dt><dd>{{ $order->paid_at?->format('d M Y, h:i A') }}</dd>
        </dl>
        @if ($order->user_proof)
          <div class="doc" style="margin-top:14px">
            <a href="{{ route('files.proof', $order) }}" target="_blank"><img src="{{ route('files.proof', $order) }}" alt="Payment proof" onerror="this.replaceWith(Object.assign(document.createElement('p'),{textContent:'Open file ↗'}))"></a>
          </div>
        @endif
      @else
        <p class="muted">Customer has not submitted payment details yet.</p>
      @endif
    </div>

    @if ($order->isOpen())
    <div class="box">
      <h3 style="margin-bottom:14px">Actions</h3>
      @if ($order->status === 'payment_submitted')
        <form method="POST" action="{{ route('admin.orders.accept', $order) }}" class="form" style="margin-bottom:18px" data-confirm="Confirm you have verified the {{ $buy ? 'INR payment in your bank' : 'USDT deposit on-chain' }}?">
          @csrf
          <label>1. Verify {{ $buy ? $order->currency . ' received in bank (amount + reference + sender name)' : 'USDT received on-chain (amount + network)' }}</label>
          <input class="input" name="note" placeholder="Optional note">
          <button class="btn btn-primary">✓ Accept {{ $buy ? 'payment' : 'deposit' }}</button>
        </form>
      @endif
      @if (in_array($order->status, ['payment_submitted', 'processing']))
        <form method="POST" action="{{ route('admin.orders.complete', $order) }}" class="form" style="margin-bottom:18px" data-confirm="Mark this order as completed?">
          @csrf
          <label>2. {{ $buy ? 'After sending USDT, enter the transaction hash' : 'After paying ' . $order->currency . ', enter the payout reference / UTR' }}</label>
          <input class="input mono" name="admin_reference" placeholder="{{ $buy ? 'USDT tx hash' : 'Payout UTR' }}" required>
          <button class="btn btn-primary">✓ Mark completed</button>
        </form>
      @endif
      <form method="POST" action="{{ route('admin.orders.reject', $order) }}" class="form" data-confirm="Reject this order?">
        @csrf
        <label>Reject order</label>
        <select class="input" onchange="this.nextElementSibling.value=this.value">
          <option value="">Choose a reason…</option>
          <option>Payment not received</option>
          <option>Amount mismatch</option>
          <option>Third-party payment — refund to source</option>
          <option>Invalid UTR / reference</option>
          <option>USDT not received on-chain</option>
          <option>Wrong network used</option>
          <option>Compliance / risk review</option>
        </select>
        <input class="input" name="reject_reason" placeholder="Reason shown to the customer" required>
        <button class="btn btn-danger">✕ Reject</button>
      </form>
    </div>
    @elseif ($order->status === 'completed')
      <div class="box"><h3>Completed</h3><p class="muted" style="margin-top:6px">{{ $buy ? 'Tx hash' : 'Payout UTR' }}: <span class="mono">{{ $order->admin_reference }}</span>
        @if ($buy && ($x = \App\Models\Order::explorerUrl($order->network, $order->admin_reference)))<a class="green" href="{{ $x }}" target="_blank" rel="noopener">Explorer ↗</a>@endif</p></div>
    @endif
  </div>

  <div>
    <div class="box">
      <div class="box-head"><h3>Customer</h3><a href="{{ route('admin.users.show', $u) }}" class="btn btn-ghost btn-sm">Profile</a></div>
      <dl class="kv" style="grid-template-columns:100px 1fr">
        <dt>Name</dt><dd>{{ $u->name }}</dd>
        <dt>Client ID</dt><dd class="mono">{{ $u->client_id }}</dd>
        <dt>KYC name</dt><dd>{{ $u->kyc?->full_name ?? '—' }}</dd>
        <dt>Country</dt><dd>{{ $u->countryName() }}</dd>
        <dt>ID</dt><dd class="mono">{{ $u->kyc ? $u->kyc->idTypeLabel() . ': ' . ($u->kyc->pan_number ?? $u->kyc->id_number) : '—' }}</dd>
        <dt>Phone</dt><dd>{{ $u->phone }} <a class="green" target="_blank" rel="noopener" href="https://wa.me/{{ $u->whatsappNumber() }}?text={{ rawurlencode('Regarding your SRM order ' . $order->order_no) }}">WhatsApp ↗</a></dd>
        <dt>Email</dt><dd>{{ $u->email }}</dd>
        <dt>KYC</dt><dd><span class="badge {{ $u->kyc_status === 'approved' ? 'ok' : 'warn' }}">{{ $u->kyc_status }}</span></dd>
      </dl>
    </div>
    <div class="box">
      <h3 style="margin-bottom:14px">Activity log</h3>
      <ul class="timeline">
        @foreach ($order->logs as $log)
          <li><b>{{ \App\Models\Order::LABELS[$log->status] ?? $log->status }}</b>
            @if ($log->note)<span class="muted"> — {{ $log->note }}</span>@endif
            <small>{{ $log->created_at->format('d M, h:i:s A') }} · {{ $log->actor?->name ?? 'System' }}</small></li>
        @endforeach
      </ul>
    </div>
  </div>
</div>
@endsection
