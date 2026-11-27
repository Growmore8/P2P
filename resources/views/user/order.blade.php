@extends('layouts.app')
@section('back', route('orders.index'))
@section('title', 'Order ' . $order->order_no)
@section('heading', 'Order details')

@section('content')
@php
  $buy = $order->isBuy();
  $d = $order->deposit_snapshot ?? [];
  $p = $order->payout_snapshot ?? [];
  $step = $order->step();
  $bad = in_array($order->status, ['rejected', 'cancelled', 'expired']);
  $pending = $order->status === 'pending_payment';
  $steps = ['Order placed', $buy ? 'Paid' : 'USDT sent', 'Verified', $buy ? 'USDT sent' : 'Paid out'];
@endphp

{{-- ===== Header: what this order is ===== --}}
<div class="o-hero">
  <div class="o-hero-top">
    <span class="badge {{ $order->side }}">{{ $buy ? 'BUY' : 'SELL' }} USDT</span>
    <span class="badge {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
  </div>
  <div class="o-amount">{{ number_format($order->usdt_amount, 2) }} <small>USDT</small></div>
  <div class="o-sub">{{ $buy ? 'for' : 'for' }} <b>{{ $order->money($order->clientFiat()) }}</b> · {{ $order->network }}</div>
  <div class="o-meta">
    <span class="mono">{{ $order->order_no }}</span>
    <button type="button" class="icon-copy" data-copy="{{ $order->order_no }}" aria-label="Copy order number">⧉</button>
    <span class="o-dot">•</span>
    <span>{{ $order->created_at->format('d M Y, h:i A') }}</span>
  </div>
</div>

{{-- ===== Progress ===== --}}
<ol class="o-steps {{ $bad ? 'is-bad' : '' }}" aria-label="Order progress">
  @foreach ($steps as $i => $label)
    <li class="{{ $i <= $step ? 'done' : '' }} {{ $i === $step + 1 && ! $bad ? 'next' : '' }}">
      <span class="o-step-dot">{{ $i <= $step ? '✓' : $i + 1 }}</span>
      <span class="o-step-label">{{ $label }}</span>
    </li>
  @endforeach
</ol>

{{-- ================= Awaiting payment ================= --}}
@if ($pending)
  <div class="o-timer">
    <span>⏱ Complete within</span>
    <b class="timer" data-expires="{{ $order->expires_at->toIso8601String() }}">--:--</b>
  </div>

  <section class="o-sec">
    <h3 class="o-sec-title"><span class="o-num">1</span>{{ $buy ? 'Pay ' . $order->money($order->fiat_amount) : 'Send ' . number_format($order->usdt_amount, 2) . ' USDT' }}</h3>

    @if ($buy)
      <div class="o-card">
        <div class="o-big-row">
          <div><small>Amount to pay (exact)</small><b>{{ $order->money($order->fiat_amount) }}</b></div>
          <button type="button" class="copy-btn" data-copy="{{ number_format((float) $order->fiat_amount, 2, '.', '') }}">Copy</button>
        </div>
        <div class="o-method"><span class="pr-ico">{{ ['upi' => '📲', 'bank' => '🏦', 'wallet' => '👛'][$d['type'] ?? ''] ?? '💳' }}</span><b>{{ $d['title'] ?? 'Payment details' }}</b></div>

        @if (($d['type'] ?? '') === 'link' && ! empty($d['pay_url']))
          <a href="{{ $d['pay_url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-block btn-lg" style="margin:10px 0 6px">Pay {{ $order->money($order->fiat_amount) }} on {{ str_contains(strtolower($d['pay_url']), 'razorpay') ? 'Razorpay' : 'the payment page' }} ↗</a>
          <ol class="o-steps-mini">
            <li>Enter exactly <b>{{ $order->money($order->fiat_amount) }}</b> on the payment page</li>
            <li>Add your order number <b class="mono">{{ $order->order_no }}</b> in the note / description</li>
            <li>After paying, copy the <b>Payment ID</b> (starts with <span class="mono">pay_</span>) and paste it below</li>
          </ol>
        @endif
        @if (($d['type'] ?? '') === 'upi')
          <div class="o-qr-wrap">
            <div class="qr" id="upiQr">@if (! empty($d['qr_image']))<img src="{{ route('files.orderQr', $order) }}" alt="UPI QR code">@endif</div>
            <small class="muted">Scan with any UPI app</small>
          </div>
          <div class="o-row"><span>UPI ID</span><b class="mono">{{ $d['upi_id'] }}</b><button type="button" class="icon-copy" data-copy="{{ $d['upi_id'] }}" aria-label="Copy UPI ID">⧉</button></div>
        @else
          @if (! empty($d['qr_image']))<div class="o-qr-wrap"><div class="qr"><img src="{{ route('files.orderQr', $order) }}" alt="Payment QR code"></div></div>@endif
          @foreach ([
            'Account name' => $d['account_holder'] ?? null,
            ($d['type'] ?? '') === 'wallet' ? 'Wallet number' : 'Account no.' => $d['account_number'] ?? null,
            $order->currency === 'INR' ? 'IFSC' : 'Bank / SWIFT code' => $d['ifsc'] ?? null,
            ($d['type'] ?? '') === 'wallet' ? 'Provider' : 'Bank' => $d['bank_name'] ?? null,
          ] as $label => $val)
            @if ($val)
              <div class="o-row"><span>{{ $label }}</span><b class="mono">{{ $val }}</b><button type="button" class="icon-copy" data-copy="{{ $val }}" aria-label="Copy {{ $label }}">⧉</button></div>
            @endif
          @endforeach
        @endif
        @if (! empty($d['instructions']))
          <div class="notice o-note">{{ $d['instructions'] }}</div>
        @endif
      </div>
    @else
      <div class="o-card">
        <div class="o-big-row">
          <div><small>Send exactly</small><b>{{ number_format($order->usdt_amount, 2) }} USDT</b></div>
          <button type="button" class="copy-btn" data-copy="{{ $order->usdt_amount }}">Copy</button>
        </div>
        <div class="o-warn">⚠ Network: <b>{{ $order->network }}</b> only — other networks or tokens are lost permanently.</div>
        <div class="o-qr-wrap"><div class="qr" id="addrQr"></div><small class="muted">Our {{ $order->network }} deposit address</small></div>
        <div class="o-address mono">{{ $order->deposit_address }}</div>
        <button type="button" class="btn btn-ghost btn-block copy-btn-wide" data-copy="{{ $order->deposit_address }}">⧉ Copy address</button>
      </div>
    @endif
  </section>

  <section class="o-sec">
    <h3 class="o-sec-title"><span class="o-num">2</span>{{ $buy ? 'Confirm your payment' : 'Confirm your transfer' }}</h3>
    <form method="POST" action="{{ route('orders.payment', $order) }}" enctype="multipart/form-data" class="form o-card">
      @csrf
      <div><label>{{ $buy ? ((($d['type'] ?? '') === 'link') ? 'Payment ID (pay_…) or UTR' : 'UTR / reference number') : 'Transaction hash (TxID)' }}</label>
        <input class="input mono" name="user_reference" value="{{ old('user_reference') }}" required autocomplete="off" inputmode="{{ $buy ? 'text' : 'text' }}"
               placeholder="{{ $buy ? '12-digit UTR from your bank / UPI app' : 'Paste the transaction hash' }}">
      </div>
      <div>
        <label>{{ $buy ? 'Payment screenshot' : 'Screenshot (optional)' }}</label>
        <label class="o-upload">
          <input type="file" name="user_proof" accept="image/*,.pdf" @required($buy) data-upload>
          <span class="o-upload-ico">⬆</span>
          <span data-upload-label>Tap to upload <small>JPG, PNG or PDF · max 5 MB</small></span>
        </label>
      </div>
      <button class="btn btn-primary btn-block btn-lg">{{ $buy ? 'I have paid' : 'I have sent USDT' }}</button>
    </form>
    <form method="POST" action="{{ route('orders.cancel', $order) }}" data-confirm="Cancel this order? Do not cancel if you have already paid." class="center" style="margin-top:12px">
      @csrf <button class="linkish" style="color:var(--red)">Cancel order</button>
    </form>
  </section>

{{-- ================= Waiting for admin ================= --}}
@elseif (in_array($order->status, ['payment_submitted', 'processing']))
  <div class="o-state">
    <div class="o-state-ico info">⏳</div>
    <h3>{{ $order->status === 'processing' ? 'Payment verified — processing' : 'Verifying your ' . ($buy ? 'payment' : 'transfer') }}</h3>
    <p>{{ $order->status === 'processing'
          ? ($buy ? 'We are sending USDT to your wallet.' : 'We are sending ' . $order->money($order->payout_fiat) . ' to your account.')
          : 'This usually takes ' . $settings['avg_completion'] . '. This page refreshes automatically.' }}</p>
    <div class="o-row" style="margin-top:14px;text-align:left"><span>Your reference</span><b class="mono">{{ $order->user_reference }}</b></div>
  </div>

{{-- ================= Completed ================= --}}
@elseif ($order->status === 'completed')
  <div class="o-state">
    <div class="o-state-ico ok">✓</div>
    <h3>Order completed</h3>
    <p>{{ $buy ? number_format($order->usdt_amount, 2) . ' USDT was sent to your wallet.' : $order->money($order->payout_fiat) . ' was paid to your account.' }}</p>
    <div class="o-row" style="margin-top:14px;text-align:left"><span>{{ $buy ? 'Tx hash' : 'Payout ref.' }}</span><b class="mono">{{ $order->admin_reference }}</b><button type="button" class="icon-copy" data-copy="{{ $order->admin_reference }}" aria-label="Copy reference">⧉</button></div>
    @if ($buy && ($x = \App\Models\Order::explorerUrl($order->network, $order->admin_reference)))
      <a href="{{ $x }}" target="_blank" rel="noopener" class="btn btn-ghost btn-block" style="margin-top:12px">View on blockchain explorer ↗</a>
    @endif
    <a href="{{ route('trade', ['side' => $order->side]) }}" class="btn btn-primary btn-block" style="margin-top:10px">{{ $buy ? 'Buy' : 'Sell' }} again</a>
  </div>

{{-- ================= Rejected / cancelled / expired ================= --}}
@else
  <div class="o-state">
    <div class="o-state-ico bad">✕</div>
    <h3>Order {{ strtolower($order->statusLabel()) }}</h3>
    @if ($order->reject_reason)<p><b>Reason:</b> {{ $order->reject_reason }}</p>@endif
    @if ($order->status === 'rejected' && $order->paid_at)
      <p>If you already paid, our team will contact you about a refund to the source account.</p>
    @endif
    <a href="{{ route('trade', ['side' => $order->side]) }}" class="btn btn-primary btn-block" style="margin-top:14px">Place a new order</a>
  </div>
@endif

{{-- ===== Details (collapsible) ===== --}}
<section class="o-sec">
  <button type="button" class="o-collapse" data-toggle="orderDetails" aria-expanded="{{ $pending ? 'false' : 'true' }}">
    <span>Order details</span><span class="pr-caret">⌄</span>
  </button>
  <div class="o-card" id="orderDetails" @if ($pending) hidden @endif>
    <div class="o-row"><span>Rate</span><b>1 USDT = {{ $order->money($order->rate) }}</b></div>
    @if ($buy)
      <div class="o-row"><span>You pay</span><b>{{ $order->money($order->fiat_amount) }}</b></div>
      <div class="o-row"><span>Network fee</span><b>{{ $order->fee_usdt }} USDT</b></div>
      <div class="o-row"><span>You receive</span><b class="green">{{ number_format($order->usdt_amount, 2) }} USDT</b></div>
      <div class="o-row o-row-stack"><span>Your wallet ({{ $order->network }})</span><b class="mono">{{ $order->wallet_address }}</b></div>
    @else
      <div class="o-row"><span>You send</span><b>{{ number_format($order->usdt_amount, 2) }} USDT</b></div>
      <div class="o-row"><span>Gross value</span><b>{{ $order->money($order->fiat_amount) }}</b></div>
      @if ((float) $order->tds_amount > 0)<div class="o-row"><span>TDS{{ $order->currency === 'INR' ? ' (Sec. 194S)' : '' }}</span><b>− {{ $order->money($order->tds_amount) }}</b></div>@endif
      <div class="o-row"><span>You receive</span><b class="green">{{ $order->money($order->payout_fiat) }}</b></div>
      <div class="o-row"><span>Payout to</span><b>{{ ($p['type'] ?? '') === 'upi' ? $p['upi_id'] : ($p['bank_name'] ?? 'Account') . ' · XXXX' . substr($p['account_number'] ?? '', -4) }}</b></div>
    @endif
  </div>
</section>

{{-- ===== Activity ===== --}}
<section class="o-sec">
  <h3 class="o-sec-title" style="font-size:15px">Activity</h3>
  <ul class="timeline o-timeline">
    @foreach ($order->logs->reverse() as $log)
      <li><b>{{ \App\Models\Order::LABELS[$log->status] ?? $log->status }}</b>
        @if ($log->note)<span class="muted"> — {{ $log->note }}</span>@endif
        <small>{{ $log->created_at->format('d M, h:i A') }}</small></li>
    @endforeach
  </ul>
</section>

<a class="btn btn-ghost btn-block" href="{{ route('support.create', ['order' => $order->order_no, 'category' => $order->isBuy() ? 'buy' : 'sell']) }}">🎧 Need help with this order? Raise a ticket</a>
@endsection

@push('scripts')
@if ($pending)
@php
  $qrData = [
    'upi' => 'upi://pay?pa=' . ($d['upi_id'] ?? '') . '&pn=' . rawurlencode('SRM FINTECH SOLUTIONS') . '&am=' . number_format((float) $order->fiat_amount, 2, '.', '') . '&cu=INR&tn=' . $order->order_no,
    'addr' => (string) $order->deposit_address,
  ];
@endphp
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  (function () {
    var q = {!! json_encode($qrData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    var upi = document.getElementById('upiQr');
    if (upi && !upi.querySelector('img')) new QRCode(upi, { text: q.upi, width: 168, height: 168 });
    var addr = document.getElementById('addrQr');
    if (addr) new QRCode(addr, { text: q.addr, width: 168, height: 168 });
    // show chosen file name in the upload box
    var up = document.querySelector('[data-upload]');
    if (up) up.addEventListener('change', function () {
      var l = document.querySelector('[data-upload-label]');
      if (up.files[0]) { l.innerHTML = '✓ ' + up.files[0].name.replace(/[<>&"]/g, '') + ' <small>Tap to change</small>'; up.closest('.o-upload').classList.add('has-file'); }
    });
  })();
</script>
@endif
@if (in_array($order->status, ['payment_submitted', 'processing']))
<script>setTimeout(function () { location.reload(); }, 30000);</script>
@endif
@endpush
