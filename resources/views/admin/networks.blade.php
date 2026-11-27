@extends('layouts.admin')
@section('title', 'Your USDT wallets')
@section('heading', 'Your USDT wallets')
@section('topActions')<button type="button" class="btn btn-primary btn-sm" data-open-add>+ Add network</button>@parent @endsection

@section('content')
<p class="muted cur-intro"><a class="green" href="{{ route('admin.payments') }}">← Payment setup</a> · Your USDT addresses. Clients send USDT here when they <b>sell</b>. Tap ✏️ to change an address.</p>

@php
  $hints = ['TRC20' => 'Tron address — starts with T (34 characters)', 'BEP20' => 'BNB Chain address — starts with 0x (42 characters)', 'ERC20' => 'Ethereum address — starts with 0x (42 characters)', 'POLYGON' => 'Polygon address — starts with 0x (42 characters)'];
  $editing = old('_net');
  $pencil = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25zm17.7-10.2a1 1 0 0 0 0-1.42l-2.33-2.33a1 1 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';
  $trash = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
@endphp

{{-- ===== Add wallet pop-up ===== --}}
@php
  $presets = collect(config('networks'))->except($networks->pluck('code')->all());
  $addErr = $errors->any() && ! $editing;
@endphp
<div class="modal {{ $addErr ? 'open' : '' }}" id="addModal" aria-hidden="{{ $addErr ? 'false' : 'true' }}">
  <form method="POST" action="{{ route('admin.networks.store') }}" class="modal-box form wl-modal" role="dialog" aria-modal="true" aria-label="Add crypto wallet">
    @csrf
    <h3>Add crypto wallet</h3>
    @if ($presets->isEmpty())
      <p class="muted" style="margin:10px 0 0">All supported networks are already added. Tap ✏️ on a network to change its address.</p>
      <div class="wl-modal-actions"><button type="button" class="btn btn-ghost" data-close-add>Close</button></div>
    @else
      <select class="input" name="code" required data-net-pick aria-label="Network">
        @foreach ($presets as $code => $p)<option value="{{ $code }}" data-hint="{{ $p['hint'] }}" @selected(old('code') === $code)>{{ $code }} · {{ $p['name'] }}</option>@endforeach
      </select>
      <input class="input" value="USDT" readonly aria-label="Coin">
      <textarea class="input mono addr-input" name="deposit_address" rows="2" spellcheck="false" autocomplete="off" placeholder="Wallet address" required data-addr>{{ old('deposit_address') }}</textarea>
      <p class="addr-check" data-addr-check data-net-hint></p>
      <input type="hidden" name="active" value="0">
      <label class="check"><input type="checkbox" name="active" value="1" checked> <span>Active</span></label>
      <div class="wl-modal-actions">
        <button type="button" class="btn btn-ghost" data-close-add>Cancel</button>
        <button class="btn btn-save">Save</button>
      </div>
    @endif
  </form>
</div>

{{-- ===== Wallet list ===== --}}
<div class="box wl-box">
  <div class="wl-head"><b>Wallets</b> <span class="muted">— {{ $networks->count() }} {{ \Illuminate\Support\Str::plural('network', $networks->count()) }}</span></div>

  @forelse ($networks as $n)
    @php
      $open = $editing === $n->code;
      $addr = (string) $n->deposit_address;
      $on = $n->buy_enabled || $n->sell_enabled;
    @endphp
    <div class="wl-item {{ $open ? 'is-open' : '' }}" id="net-{{ $n->code }}">
      <div class="wl-row">
        <span class="wl-chip">{{ $n->code }}</span>
        <div class="wl-main">
          <b>USDT <span class="muted wl-name">· {{ $n->name }}</span></b>
          @if ($addr)
            <span class="wl-addr mono">{{ $addr }}</span>
          @else
            <span class="wl-addr wl-missing">No address yet — clients can't sell on {{ $n->code }}</span>
          @endif
        </div>
        <span class="wl-status {{ ! $on ? 'off' : ($n->sell_enabled && ! $addr ? 'warn' : 'ok') }}">{{ ! $on ? 'Off' : ($n->sell_enabled && ! $addr ? 'Add address' : 'Active') }}</span>
        <button type="button" class="wl-btn edit" data-wl-edit="{{ $n->code }}" title="Edit {{ $n->code }}" aria-label="Edit {{ $n->code }}" aria-expanded="{{ $open ? 'true' : 'false' }}">{!! $pencil !!}</button>
        <form method="POST" action="{{ route('admin.networks.destroy', $n) }}" data-confirm="Remove the {{ $n->code }} network? Clients won't be able to choose it any more.">
          @csrf @method('DELETE')
          <button class="wl-btn del" title="Remove {{ $n->code }}" aria-label="Remove {{ $n->code }}">{!! $trash !!}</button>
        </form>
      </div>

      {{-- Edit panel --}}
      <form method="POST" action="{{ route('admin.networks.update', $n) }}" class="form wl-edit" data-wl-form="{{ $n->code }}" @unless ($open) hidden @endunless>
        @csrf @method('PUT')
        <input type="hidden" name="_net" value="{{ $n->code }}">
        <label>Your USDT deposit address <small>{{ $hints[$n->code] ?? 'for sell orders' }}</small></label>
        <textarea class="input mono addr-input" name="deposit_address" rows="2" spellcheck="false" autocomplete="off"
                  placeholder="Paste your {{ $n->code }} USDT address" data-addr>{{ $open ? old('deposit_address') : $addr }}</textarea>
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
          <span></span>
          <div class="cur-actions-r">
            <button type="button" class="btn btn-ghost" data-wl-edit="{{ $n->code }}">Cancel</button>
            <button class="btn btn-primary">Save {{ $n->code }}</button>
          </div>
        </div>
      </form>
    </div>
  @empty
    <p class="muted" style="text-align:center;padding:20px 0">No networks yet — tap <b>+ Add network</b>.</p>
  @endforelse
</div>

<script>
(function () {
  var modal = document.getElementById('addModal');
  function openAdd(on) {
    modal.classList.toggle('open', on); modal.setAttribute('aria-hidden', on ? 'false' : 'true');
    if (on) { var t = modal.querySelector('textarea'); if (t) setTimeout(function () { t.focus(); }, 60); }
  }
  document.querySelectorAll('[data-open-add]').forEach(function (b) { b.addEventListener('click', function () { openAdd(true); }); });
  document.querySelectorAll('[data-close-add]').forEach(function (b) { b.addEventListener('click', function () { openAdd(false); }); });
  modal.addEventListener('click', function (e) { if (e.target === modal) openAdd(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') openAdd(false); });
  var pickNet = modal.querySelector('[data-net-pick]'), hint = modal.querySelector('[data-net-hint]');
  if (pickNet) {
    var showHint = function () { var o = pickNet.selectedOptions[0]; if (hint && !modal.querySelector('textarea').value) hint.textContent = o ? pickNet.value + ' address ' + o.dataset.hint : ''; };
    pickNet.addEventListener('change', showHint); showHint();
  }

  // ✏️ opens / closes the edit panel under the row
  document.querySelectorAll('[data-wl-edit]').forEach(function (b) {
    b.addEventListener('click', function () {
      var code = b.dataset.wlEdit, f = document.querySelector('[data-wl-form="' + code + '"]');
      f.hidden = !f.hidden;
      f.closest('.wl-item').classList.toggle('is-open', !f.hidden);
      document.querySelectorAll('[data-wl-edit="' + code + '"]').forEach(function (x) { x.setAttribute('aria-expanded', f.hidden ? 'false' : 'true'); });
      if (!f.hidden) f.querySelector('textarea').focus();
    });
  });

  // Show the first and last 6 characters so the admin can compare with their wallet app.
  document.querySelectorAll('[data-addr]').forEach(function (t) {
    var out = t.parentNode.querySelector('[data-addr-check]');
    t.addEventListener('input', function () {
      var v = t.value.replace(/\s+/g, ''); t.value = v;
      out.innerHTML = v.length > 10 ? 'Check it matches your wallet: <b>' + v.slice(0, 6) + '</b> … <b>' + v.slice(-6) + '</b>' : '';
    });
  });

  // Link like #net-TRC20 opens that wallet
  if (location.hash.indexOf('#net-') === 0) { var b = document.querySelector('[data-wl-edit="' + location.hash.slice(5) + '"]'); if (b) b.click(); }
})();
</script>
@endsection
