@extends('layouts.app')
@section('title', ($side === 'sell' ? 'Sell' : 'Buy') . ' USDT')

@section('content')
@php $u = auth()->user(); @endphp

@if ($openOrder)
  <a href="{{ route('orders.show', $openOrder) }}" class="lrow" style="background:var(--brand-soft)">
    <div class="lmain"><b>Open order {{ $openOrder->order_no }}</b><small>{{ $openOrder->statusLabel() }} · tap to continue</small></div>
    <span class="pr-caret">›</span>
  </a>
@endif
@if (! $currency)
  <div class="alert alert-info">Trading in {{ $u->currencyCode() }} isn't available in {{ $u->countryName() }} yet. Please <a class="green" href="https://wa.me/{{ $settings['support_whatsapp'] }}" target="_blank" rel="noopener">contact support</a> for options.</div>
@elseif (! $pricing['trading_enabled'])
  <div class="alert alert-error">Trading is temporarily paused. Please check back later.</div>
@endif

<form method="POST" action="{{ route('trade.store') }}" style="display:flex;flex-direction:column;flex:1">
  @csrf
  <div data-calc data-mode="trade" data-side="{{ old('side', $side) }}" data-amount="{{ $amount ?? '' }}">
    <input type="hidden" name="side" value="{{ old('side', $side) }}">
    <input type="hidden" name="amount" value="">
    <div class="xtabs">
      <button type="button" class="tab buy" data-side="buy">Buy</button>
      <button type="button" class="tab sell" data-side="sell">Sell</button>
    </div>

    <div class="xfield">
      <label data-l="pay">Pay</label>
      <div class="xrow">
        <input type="text" inputmode="decimal" data-in="top" placeholder="0" autocomplete="off" aria-label="Amount">
        <span class="chip" data-coin="top"></span>
      </div>
    </div>
    <div class="xfield">
      <label>You get</label>
      <div class="xrow">
        <input type="text" inputmode="decimal" data-in="bot" placeholder="0" autocomplete="off" aria-label="You get">
        <span class="chip" data-coin="bot"></span>
      </div>
    </div>
    <div class="xinfo" data-info></div>
    <div class="err" data-err></div>

    <div class="pick-label">Network</div>
    <select class="pick-row" data-net name="network" aria-label="Network" style="appearance:auto;margin-bottom:14px">
      @foreach ($pricing['networks'] as $n)
        <option value="{{ $n['code'] }}" @selected(old('network', $network) === $n['code'])>USDT · {{ $n['name'] }}</option>
      @endforeach
    </select>

    {{-- BUY --}}
    <div data-only="buy">
      <div class="pick-label">Receive USDT in wallet</div>
      <input class="input mono" name="wallet_address" value="{{ old('wallet_address') }}" placeholder="Paste your wallet address" autocomplete="off" style="margin-bottom:14px">

      <div class="pick-label">Pay using</div>
      <div class="paybox">
        <button type="button" class="pick-row" data-sheet-open="sheetPay">
          <span data-sheet-label style="display:flex;gap:12px;align-items:center;flex:1">
            <span class="pr-ico">💳</span><span class="pr-main"><b>Choose payment method</b><small>{{ $methods->pluck('title')->take(2)->join(' or ') ?: 'No methods available' }}</small></span>
          </span>
          <span class="pr-caret">⌄</span>
        </button>
        <div class="ptotal"><span data-total></span><button type="button" data-toggle="calcDetails">Show details</button></div>
        <div class="rate-box" data-box id="calcDetails" hidden></div>
      </div>
    </div>

    {{-- SELL --}}
    <div data-only="sell">
      <div class="pick-label">Receive money in</div>
      <div class="paybox">
        <button type="button" class="pick-row" data-sheet-open="sheetPayout">
          <span data-sheet-label style="display:flex;gap:12px;align-items:center;flex:1">
            <span class="pr-ico">🏦</span><span class="pr-main"><b>{{ $accounts->isEmpty() ? 'Add a bank account / UPI' : 'Choose payout account' }}</b><small>Must be in your own name</small></span>
          </span>
          <span class="pr-caret">⌄</span>
        </button>
        <div class="ptotal"><span data-total></span><button type="button" data-toggle="calcDetails2">Show details</button></div>
        <div class="rate-box" id="calcDetails2" hidden data-box2></div>
      </div>
    </div>
  </div>

  <div class="stick-bottom">
    <label class="check" style="font-size:12.5px;margin-bottom:12px"><input type="checkbox" name="confirm" value="1" @checked(old('confirm'))> I'm trading for myself with my own account / wallet and accept the <a href="{{ route('legal') }}#p2p-terms" target="_blank" class="green">P2P Terms</a>.</label>
    @if (! $currency)
      <button class="btn btn-primary btn-block btn-lg" disabled>Not available in your country yet</button>
    @elseif ($u->isKycApproved())
      <button class="btn btn-primary btn-block btn-lg" @disabled(! $pricing['trading_enabled'])>Proceed</button>
    @else
      <a href="{{ route('account.limits') }}" class="btn btn-primary btn-block btn-lg">Verify to proceed</a>
      <p class="muted center" style="font-size:12.5px;margin-top:8px">Complete a quick verification before your first order.</p>
    @endif
    <p class="muted center" style="font-size:12px;margin-top:10px">🛡 Rate locked when you proceed · pay within {{ $settings['order_expiry_minutes'] }} min</p>
  </div>
</form>
@endsection

@section('sheets')
  <div class="sheet-backdrop" id="sheetPay">
    <div class="sheet">
      <div class="sheet-head">Pay using <button type="button" data-sheet-close aria-label="Close">×</button></div>
      @forelse ($methods as $m)
        <label class="sheet-opt">
          <input type="radio" form="tradeFormProxy" name="deposit_method_id_ui" value="{{ $m->id }}" data-target="deposit_method_id" @checked(old('deposit_method_id', $loop->first ? $m->id : null) == $m->id)>
          <span data-label style="display:flex;gap:12px;align-items:center;flex:1">
            <span class="pr-ico">{{ ['upi' => '📲', 'bank' => '🏦', 'wallet' => '👛', 'link' => '🔗'][$m->type] ?? '💳' }}</span>
            <span class="pr-main"><b>{{ $m->title }}</b><small>{{ $m->subtitle() }}@if ((float) $m->max_amount > 0) · up to {{ money($m->max_amount, $m->currency, 0) }}@endif</small></span>
          </span>
          @if ($loop->first)<span class="tag-ok">Recommended</span>@endif
          <span class="pr-caret">›</span>
        </label>
      @empty
        <p class="empty">No payment methods are available right now.</p>
      @endforelse
    </div>
  </div>

  <div class="sheet-backdrop" id="sheetPayout">
    <div class="sheet">
      <div class="sheet-head">Receive money in <button type="button" data-sheet-close aria-label="Close">×</button></div>
      @foreach ($accounts as $a)
        <label class="sheet-opt">
          <input type="radio" name="bank_account_id_ui" value="{{ $a->id }}" data-target="bank_account_id" @checked(old('bank_account_id', $loop->first ? $a->id : null) == $a->id)>
          <span data-label style="display:flex;gap:12px;align-items:center;flex:1">
            <span class="pr-ico">{{ $a->type === 'upi' ? '📲' : '🏦' }}</span>
            <span class="pr-main"><b>{{ $a->label() }}</b><small>{{ $a->holder_name }}</small></span>
          </span>
          <span class="pr-caret">›</span>
        </label>
      @endforeach
      <a href="{{ route('accounts.index') }}" class="sheet-opt" style="color:var(--brand);font-weight:700">+ Add bank account / UPI</a>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  // Sheets live outside the <form>; mirror the chosen radio into hidden inputs inside it.
  (function () {
    var form = document.querySelector('form[action$="/trade"]');
    ['deposit_method_id', 'bank_account_id'].forEach(function (name) {
      var h = document.createElement('input'); h.type = 'hidden'; h.name = name; form.appendChild(h);
      var sync = function () { var r = document.querySelector('input[data-target="' + name + '"]:checked'); h.value = r ? r.value : ''; };
      document.querySelectorAll('input[data-target="' + name + '"]').forEach(function (r) { r.removeAttribute('form'); r.addEventListener('change', sync); });
      sync();
    });
    // Keep the sell "details" box in step with the calculator box.
    var a = document.getElementById('calcDetails'), b = document.querySelector('[data-box2]');
    if (a && b) new MutationObserver(function () { b.innerHTML = a.innerHTML; }).observe(a, { childList: true, subtree: true });
    if (a && b) b.innerHTML = a.innerHTML;
  })();
</script>
@endpush
