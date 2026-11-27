@extends('layouts.admin')
@section('title', 'Your USDT wallets')
@section('heading', 'Your USDT wallets')
@section('topActions')<button type="button" class="btn btn-primary btn-sm" data-modal-open="addModal">+ Add wallet</button>@parent @endsection

@section('content')
<p class="muted cur-intro"><a class="green" href="{{ route('admin.payments') }}">← Payment setup</a> · Your USDT addresses. Clients send USDT here when they <b>sell</b>. You can add several addresses on the same network — new sell orders take turns between the active ones.</p>

@php
  $pencil = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25zm17.7-10.2a1 1 0 0 0 0-1.42l-2.33-2.33a1 1 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';
  $trash = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
  $editingWallet = old('_wallet');
  $wallets = $networks->flatMap(fn ($n) => $n->wallets->map(fn ($w) => $w->setRelation('network', $n)));
  $addOpen = $errors->any() && $editingWallet === 'new';
@endphp

{{-- ===== Add wallet pop-up ===== --}}
<div class="modal {{ $addOpen ? 'open' : '' }}" id="addModal" data-admin-modal aria-hidden="{{ $addOpen ? 'false' : 'true' }}">
  <form method="POST" action="{{ route('admin.wallets.store') }}" class="modal-box form wl-modal" role="dialog" aria-modal="true" aria-label="Add crypto wallet">
    @csrf
    <h3>Add crypto wallet</h3>
    <select class="input" name="code" required data-net-pick aria-label="Network">
      @foreach (config('networks') as $code => $p)<option value="{{ $code }}" data-hint="{{ $p['hint'] }}" @selected(old('code') === $code)>{{ $code }}</option>@endforeach
    </select>
    <input class="input" value="USDT" readonly aria-label="Coin">
    <input class="input mono" name="address" value="{{ $addOpen ? old('address') : '' }}" spellcheck="false" autocomplete="off" placeholder="Wallet address" required data-addr>
    <p class="addr-check" data-addr-check data-net-hint></p>
    <label class="check"><input type="checkbox" name="active" value="1" checked> <span>Active</span></label>
    <div class="wl-modal-actions">
      <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
      <button class="btn btn-save">Save</button>
    </div>
  </form>
</div>

{{-- ===== Addresses (one row each) ===== --}}
<div class="box wl-box">
  <div class="wl-head"><b>Wallets</b> <span class="muted">— {{ $wallets->where('is_active', true)->count() }} active of {{ $wallets->count() }}</span></div>

  @forelse ($wallets as $w)
    @php $open = (string) $editingWallet === (string) $w->id; @endphp
    <div class="wl-item {{ $open ? 'is-open' : '' }} {{ $w->is_active ? '' : 'is-off' }}" id="wallet-{{ $w->id }}">
      <div class="wl-row">
        <span class="wl-chip">{{ $w->network->code }}</span>
        <div class="wl-main">
          <b>USDT <span class="muted wl-name">· {{ $w->network->name }}</span></b>
          <span class="wl-addr mono">{{ $w->address }}</span>
        </div>
        <span class="wl-status {{ $w->is_active ? 'ok' : 'off' }}"><i class="wl-dot"></i>{{ $w->is_active ? 'Active' : 'Inactive' }}</span>
        <button type="button" class="wl-btn edit" data-wl-edit="{{ $w->id }}" title="Edit" aria-label="Edit address" aria-expanded="{{ $open ? 'true' : 'false' }}">{!! $pencil !!}</button>
        <form method="POST" action="{{ route('admin.wallets.destroy', $w) }}" data-confirm="Remove this {{ $w->network->code }} address? Orders already placed keep their address.">
          @csrf @method('DELETE')
          <button class="wl-btn del" title="Remove" aria-label="Remove address">{!! $trash !!}</button>
        </form>
      </div>
      <form method="POST" action="{{ route('admin.wallets.update', $w) }}" class="form wl-edit wl-edit-row" data-wl-form="{{ $w->id }}" @unless ($open) hidden @endunless>
        @csrf @method('PUT')
        <input class="input mono" name="address" value="{{ $open ? old('address') : $w->address }}" spellcheck="false" autocomplete="off" required data-addr aria-label="Wallet address">
        <label class="check"><input type="checkbox" name="active" value="1" @checked($open ? old('active') : $w->is_active)> <span>Active</span></label>
        <button type="button" class="btn btn-ghost btn-sm" data-wl-edit="{{ $w->id }}">Cancel</button>
        <button class="btn btn-save btn-sm">Save</button>
        <p class="addr-check" data-addr-check></p>
      </form>
    </div>
  @empty
    <p class="muted" style="text-align:center;padding:20px 0">No wallet addresses yet — tap <b>+ Add wallet</b>. Clients can't sell until you add one.</p>
  @endforelse
</div>

{{-- ===== Network settings: fee and buy / sell per network ===== --}}
<div class="box wl-box">
  <div class="wl-head"><b>Network settings</b> <span class="muted">— fee taken from USDT on buy orders, and whether clients can buy / sell on each network</span></div>
  @foreach ($networks as $n)
    @php $active = $n->wallets->where('is_active', true)->count(); $known = array_key_exists($n->code, config('networks')); @endphp
    <form method="POST" action="{{ route('admin.networks.update', $n) }}" class="form wl-item net-set">
      @csrf @method('PUT')
      <span class="wl-chip">{{ $n->code }}</span>
      <div class="wl-main"><b>{{ $n->name }}</b><span class="wl-addr">{{ $active }} active {{ \Illuminate\Support\Str::plural('address', $active) }}{{ $n->sell_enabled && ! $active ? ' — selling paused until you add one' : '' }}</span></div>
      <label class="net-fee-inline">Fee <input class="input" type="number" step="0.01" min="0" name="fee" value="{{ $n->fee }}" required> USDT</label>
      <label class="check"><input type="checkbox" name="buy_enabled" value="1" @checked($n->buy_enabled)> <span>Buy</span></label>
      <label class="check"><input type="checkbox" name="sell_enabled" value="1" @checked($n->sell_enabled)> <span>Sell</span></label>
      <button class="btn btn-ghost btn-sm">Save</button>
      @unless ($known)
        <button type="submit" form="del-net-{{ $n->id }}" class="wl-btn del" title="Remove {{ $n->code }}" aria-label="Remove network">{!! $trash !!}</button>
      @endunless
    </form>
    @unless ($known)
      <form method="POST" action="{{ route('admin.networks.destroy', $n) }}" id="del-net-{{ $n->id }}" data-confirm="Remove the {{ $n->code }} network and its addresses? Clients won't be able to choose it any more.">@csrf @method('DELETE')</form>
    @endunless
  @endforeach
</div>

<script>
(function () {
  // ✏️ opens / closes the edit row
  document.querySelectorAll('[data-wl-edit]').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = b.dataset.wlEdit, f = document.querySelector('[data-wl-form="' + id + '"]');
      f.hidden = !f.hidden;
      f.closest('.wl-item').classList.toggle('is-open', !f.hidden);
      document.querySelectorAll('[data-wl-edit="' + id + '"]').forEach(function (x) { x.setAttribute('aria-expanded', f.hidden ? 'false' : 'true'); });
      if (!f.hidden) f.querySelector('[data-addr]').focus();
    });
  });

  // Strip spaces and show first / last 6 characters to compare with the wallet app
  document.querySelectorAll('[data-addr]').forEach(function (t) {
    var out = t.closest('form').querySelector('[data-addr-check]');
    t.addEventListener('input', function () {
      var v = t.value.replace(/\s+/g, ''); t.value = v;
      if (out) out.innerHTML = v.length > 10 ? 'Check it matches your wallet: <b>' + v.slice(0, 6) + '</b> … <b>' + v.slice(-6) + '</b>' : '';
    });
  });

  // Format hint for the chosen network in the pop-up
  var pick = document.querySelector('[data-net-pick]'), hint = document.querySelector('[data-net-hint]');
  if (pick && hint) {
    var show = function () { var o = pick.selectedOptions[0]; if (!document.querySelector('#addModal [data-addr]').value) hint.textContent = o ? pick.value + ' address ' + o.dataset.hint : ''; };
    pick.addEventListener('change', show); show();
  }
})();
</script>
@endsection
