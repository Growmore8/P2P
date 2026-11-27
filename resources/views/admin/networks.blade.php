@extends('layouts.admin')
@section('title', 'Your USDT wallets')
@section('heading', 'Your USDT wallets')
@section('topActions')<button type="button" class="btn btn-primary btn-sm" data-open-add>+ Add network</button>@parent @endsection

@section('content')
<p class="muted cur-intro"><a class="green" href="{{ route('admin.payments') }}">← Payment setup</a> · Paste your own USDT address for each network. Clients send USDT here when they <b>sell</b>. For <b>buy</b> orders you send USDT from your wallet, so no address is needed.</p>

@php
  $hints = ['TRC20' => 'Tron address — starts with T (34 characters)', 'BEP20' => 'BNB Chain address — starts with 0x (42 characters)', 'ERC20' => 'Ethereum address — starts with 0x (42 characters)', 'POLYGON' => 'Polygon address — starts with 0x (42 characters)'];
  $editing = old('_net');
@endphp

{{-- ===== Add network (hidden until "+ Add network") ===== --}}
<form method="POST" action="{{ route('admin.networks.store') }}" class="box form cur-add" id="add" @unless ($errors->any() && ! $editing) hidden @endunless>
  @csrf
  <div class="box-head"><h3>Add a network</h3><button type="button" class="icon-btn" data-close-add aria-label="Close">✕</button></div>
  <div class="cur-grid">
    <div><label>Code</label><input class="input" name="code" value="{{ old('code') }}" placeholder="e.g. SOL" required></div>
    <div><label>Name shown to clients</label><input class="input" name="name" value="{{ old('name') }}" placeholder="e.g. Solana (SPL)" required></div>
    <div><label>Fee on buy <small>USDT</small></label><input class="input" type="number" step="0.01" name="fee" value="{{ old('fee', 1) }}" required></div>
  </div>
  <div style="margin-top:12px"><label>Your USDT deposit address <small>for sell orders</small></label>
    <textarea class="input mono addr-input" name="deposit_address" rows="2" placeholder="Paste your USDT address for this network">{{ old('deposit_address') }}</textarea></div>
  <div style="margin-top:12px"><label>Address format check <small>optional, advanced</small></label><input class="input mono" name="address_regex" value="{{ old('address_regex') }}" placeholder="Leave empty if unsure"></div>
  <div class="cur-actions">
    <div class="net-toggles">
      <label class="switch cur-switch"><input type="checkbox" name="buy_enabled" value="1" checked> Buy</label>
      <label class="switch cur-switch"><input type="checkbox" name="sell_enabled" value="1" checked> Sell</label>
    </div>
    <button class="btn btn-primary">Add network</button>
  </div>
</form>

@foreach ($networks as $n)
  @php
    $ready = $n->sell_enabled && $n->deposit_address;
    $open = $editing === $n->code;
    $addr = (string) $n->deposit_address;
  @endphp
  <div class="box cur-card net-card {{ ($n->buy_enabled || $n->sell_enabled) ? '' : 'is-off' }}" id="net-{{ $n->code }}">
    <div class="cur-head">
      <span class="pay-code">{{ $n->code }}</span>
      <div class="cur-title">
        <h3>{{ $n->name }}</h3>
        <div class="chips">
          <span class="badge {{ $n->buy_enabled ? 'ok' : '' }}">{{ $n->buy_enabled ? '✓ Buy on' : 'Buy off' }}</span>
          @if (! $n->sell_enabled)<span class="badge">Sell off</span>
          @elseif ($ready)<span class="badge ok">✓ Sell ready</span>
          @else<span class="badge warn">Sell: add your address</span>@endif
          <span class="badge">Fee {{ rtrim(rtrim(number_format($n->fee, 2), '0'), '.') }} USDT</span>
        </div>
      </div>
    </div>

    <form method="POST" action="{{ route('admin.networks.update', $n) }}" class="form">
      @csrf @method('PUT')
      <input type="hidden" name="_net" value="{{ $n->code }}">

      <label>Your USDT deposit address <small>{{ $hints[$n->code] ?? 'for sell orders' }}</small></label>
      <textarea class="input mono addr-input" name="deposit_address" rows="2" spellcheck="false" autocomplete="off"
                placeholder="Paste your {{ $n->code }} USDT address — clients can't sell on {{ $n->code }} until you add it" data-addr>{{ $open ? old('deposit_address') : $addr }}</textarea>
      <p class="addr-check" data-addr-check>@if (strlen($addr) > 10)Check it matches your wallet: <b>{{ substr($addr, 0, 6) }}</b> … <b>{{ substr($addr, -6) }}</b>@endif</p>

      <div class="net-row">
        <div class="net-toggles">
          <label class="switch cur-switch"><input type="checkbox" name="buy_enabled" value="1" @checked($open ? old('buy_enabled') : $n->buy_enabled)> Buy</label>
          <label class="switch cur-switch"><input type="checkbox" name="sell_enabled" value="1" @checked($open ? old('sell_enabled') : $n->sell_enabled)> Sell</label>
        </div>
        <div class="net-fee"><label>Fee on buy <small>USDT</small></label><input class="input" type="number" step="0.01" name="fee" value="{{ $open ? old('fee') : $n->fee }}" required></div>
      </div>

      <details class="net-adv" @if ($open && $errors->hasAny(['name', 'address_regex', 'sort'])) open @endif>
        <summary>Advanced settings</summary>
        <div class="cur-grid" style="margin-top:12px">
          <div><label>Name shown to clients</label><input class="input" name="name" value="{{ $open ? old('name') : $n->name }}" required></div>
          <div><label>Order in list</label><input class="input" type="number" name="sort" value="{{ $open ? old('sort') : $n->sort }}"></div>
          <div class="span-2"><label>Address format check <small>don't change unless you know</small></label><input class="input mono" name="address_regex" value="{{ $open ? old('address_regex') : $n->address_regex }}"></div>
        </div>
      </details>

      <div class="cur-actions">
        <button class="btn btn-primary">Save {{ $n->code }}</button>
        <button type="submit" form="del-{{ $n->code }}" class="btn btn-ghost btn-sm cur-del">🗑 Remove network</button>
      </div>
    </form>
    <form method="POST" action="{{ route('admin.networks.destroy', $n) }}" id="del-{{ $n->code }}" data-confirm="Remove the {{ $n->code }} network? Clients won't be able to choose it any more.">@csrf @method('DELETE')</form>
  </div>
@endforeach

<script>
(function () {
  var add = document.getElementById('add');
  function openAdd(on) { add.hidden = !on; if (on) add.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  document.querySelectorAll('[data-open-add]').forEach(function (b) { b.addEventListener('click', function () { openAdd(true); }); });
  document.querySelectorAll('[data-close-add]').forEach(function (b) { b.addEventListener('click', function () { openAdd(false); }); });

  // Show the first and last 6 characters so the admin can compare with their wallet app.
  document.querySelectorAll('[data-addr]').forEach(function (t) {
    var out = t.parentNode.querySelector('[data-addr-check]');
    t.addEventListener('input', function () {
      var v = t.value.replace(/\s+/g, ''); t.value = v;
      out.innerHTML = v.length > 10 ? 'Check it matches your wallet: <b>' + v.slice(0, 6) + '</b> … <b>' + v.slice(-6) + '</b>' : '';
    });
  });
})();
</script>
@endsection
