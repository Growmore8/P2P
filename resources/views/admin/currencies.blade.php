@extends('layouts.admin')
@section('title', 'Rates & currencies')
@section('heading', 'Rates & currencies')
@section('topActions')<button type="button" class="btn btn-primary btn-sm" data-open-add>+ Add currency</button>@parent @endsection

@section('content')
<p class="muted cur-intro"><a class="green" href="{{ route('admin.payments') }}">← Payment setup</a> · Your USDT prices for each currency. Tap <b>Edit</b> to change rates or switch a currency on / off.</p>

@php $editing = old('_cur'); @endphp

@if ($newCode = session('suggest_pay'))
  @php $pre = \App\Services\MarketRate::presets($newCode); @endphp
  <div class="box next-step">
    <h3>Next: how will {{ $newCode }} clients pay you?</h3>
    <p class="muted" style="font-size:13.5px;margin:4px 0 12px">These are the usual ways to pay in this country. Tap one to add it — you only need to enter your own account details.</p>
    <div class="chips">
      @foreach ($pre['pay'] as [$type, $title])
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.methods.create', ['currency' => $newCode, 'type' => $type, 'title' => $title]) }}">+ {{ $title }}</a>
      @endforeach
    </div>
  </div>
@endif

{{-- ===== Add currency (hidden until "+ Add currency") ===== --}}
<form method="POST" action="{{ route('admin.currencies.store') }}" class="box form cur-add" id="add" @if (! ($errors->any() && ! $editing) && $currencies->isNotEmpty()) hidden @endif>
  @csrf
  <div class="box-head"><h3>Add a currency</h3><button type="button" class="icon-btn" data-close-add aria-label="Close">✕</button></div>
  <p class="muted" style="font-size:13px;margin:-6px 0 14px">Pick the currency — its name and symbol are set automatically. Keep it switched off until its payment methods are ready.</p>
  <div class="cur-grid">
    <div class="span-2"><label>Currency</label>
      <select class="input" name="code" required data-cur-pick>
        @foreach ($available as $code)<option value="{{ $code }}" @selected(old('code') === $code)>{{ $code }} · {{ config('currency_meta.' . $code . '.0', $code) }} — {{ collect(config('countries'))->filter(fn ($c) => $c[2] === $code)->pluck(0)->join(', ') }}</option>@endforeach
      </select>
      <span class="hint" data-cur-auto></span>
      <p class="market-line" data-market></p>
    </div>
    <div><label>Buy rate <small>client pays / USDT</small></label><input class="input big" type="number" step="0.0001" name="buy_rate" value="{{ old('buy_rate') }}" required></div>
    <div><label>Sell rate <small>client receives / USDT</small></label><input class="input big" type="number" step="0.0001" name="sell_rate" value="{{ old('sell_rate') }}" required></div>
    <div><label>Min per order</label><input class="input" type="number" step="0.01" name="min_amount" value="{{ old('min_amount', 0) }}" required></div>
    <div><label>Max per order <small>0 = no limit</small></label><input class="input" type="number" step="0.01" name="max_amount" value="{{ old('max_amount', 0) }}" required></div>
    <div><label>TDS on sell (%)</label><input class="input" type="number" step="0.01" name="tds_percent" value="{{ old('tds_percent', 0) }}" required></div>
  </div>
  <p class="hint" style="margin:12px 0 0">Monthly limits are set automatically from the INR limits at this currency's rate.</p>
  <div class="preset-box" data-presets hidden>
    <label class="switch cur-switch preset-check"><input type="checkbox" name="add_receive" value="1" checked> Also add the usual ways clients get paid: <b data-receive></b></label>
    <p class="hint" style="margin:8px 0 0">After adding, you'll be shown the usual ways clients pay you (<span data-pay></span>) to set up with your own account details.</p>
  </div>
  <div class="cur-actions">
    <label class="switch cur-switch"><input type="checkbox" name="is_active" value="1"> Switch on now</label>
    <button class="btn btn-primary">Add currency</button>
  </div>
</form>

{{-- ===== Currency cards ===== --}}
@forelse ($currencies as $c)
  @php
    $orders = (int) ($orderCounts[$c->code] ?? 0);
    $canDelete = ! $c->isBase() && $orders === 0;
    $open = $editing === $c->code;
    $sym = $c->symbol;
  @endphp
  <div class="box cur-card {{ $c->is_active ? '' : 'is-off' }}" id="cur-{{ $c->code }}">
    <div class="cur-head">
      <span class="pay-code">{{ $c->code }}</span>
      <div class="cur-title">
        <h3>{{ $c->name }} <span class="muted">({{ trim($sym) }})</span></h3>
        <div class="chips">
          @foreach (array_slice($c->countryCodes(), 0, 5) as $cc)<span class="chip-sm"><img src="https://flagcdn.com/w20/{{ strtolower($cc) }}.png" alt="">{{ $cc }}</span>@endforeach
          @if (count($c->countryCodes()) > 5)<span class="chip-sm">+{{ count($c->countryCodes()) - 5 }}</span>@endif
          @if ($c->isBase())<span class="chip-sm cur-main">Main currency</span>@endif
        </div>
      </div>
      <span class="badge {{ $c->is_active ? 'ok' : 'bad' }} cur-status">{{ $c->is_active ? 'Active' : 'Off' }}</span>
    </div>

    <div class="cur-stats">
      <div><span>Buy rate</span><b>{{ $sym }}{{ number_format($c->buy_rate, 2) }}</b><small>client pays / USDT</small></div>
      <div><span>Sell rate</span><b>{{ $sym }}{{ number_format($c->sell_rate, 2) }}</b><small>client receives / USDT</small></div>
      <div class="is-margin"><span>Your margin</span><b>{{ $sym }}{{ number_format($c->buy_rate - $c->sell_rate, 2) }}</b><small>per USDT</small></div>
      <div><span>Per order</span><b>{{ money($c->min_amount, $c->code, 0) }} – {{ (float) $c->max_amount > 0 ? money($c->max_amount, $c->code, 0) : 'no limit' }}</b><small>min – max</small></div>
      <div><span>TDS on sell</span><b>{{ rtrim(rtrim(number_format($c->tds_percent, 2), '0'), '.') }}%</b><small>{{ $orders }} {{ \Illuminate\Support\Str::plural('order', $orders) }} so far</small></div>
    </div>

    <div class="cur-btns">
      <button type="button" class="btn btn-ghost btn-sm" data-edit="{{ $c->code }}" aria-expanded="{{ $open ? 'true' : 'false' }}">✏️ Edit</button>
      @if ($canDelete)
        <form method="POST" action="{{ route('admin.currencies.destroy', $c) }}" data-confirm="Delete {{ $c->code }} ({{ $c->name }})? Its payment methods are deleted too. This cannot be undone.">
          @csrf @method('DELETE')
          <button class="btn btn-ghost btn-sm cur-del">🗑 Delete</button>
        </form>
      @else
        <span class="btn btn-ghost btn-sm is-disabled" title="{{ $c->isBase() ? 'Main currency — all limits are based on it' : 'Has orders — keep for records. Switch it off instead.' }}">🗑 Delete</span>
      @endif
    </div>

    {{-- Edit form (opens with ✏️ Edit) --}}
    <form method="POST" action="{{ route('admin.currencies.update', $c) }}" class="form cur-edit" data-edit-form="{{ $c->code }}" @unless ($open) hidden @endunless>
      @csrf @method('PUT')
      <input type="hidden" name="_cur" value="{{ $c->code }}">
      <div class="cur-grid">
        <div><label>Buy rate <small>client pays / USDT</small></label><input class="input big" type="number" step="0.0001" name="buy_rate" value="{{ $open ? old('buy_rate') : $c->buy_rate }}" required data-buy></div>
        <div><label>Sell rate <small>client receives / USDT</small></label><input class="input big" type="number" step="0.0001" name="sell_rate" value="{{ $open ? old('sell_rate') : $c->sell_rate }}" required data-sell></div>
        <div><label>Min per order</label><input class="input" type="number" step="0.01" name="min_amount" value="{{ $open ? old('min_amount') : $c->min_amount }}" required></div>
        <div><label>Max per order <small>0 = no limit</small></label><input class="input" type="number" step="0.01" name="max_amount" value="{{ $open ? old('max_amount') : $c->max_amount }}" required></div>
        <div><label>TDS on sell (%)</label><input class="input" type="number" step="0.01" name="tds_percent" value="{{ $open ? old('tds_percent') : $c->tds_percent }}" required></div>
      </div>
      <p class="cur-live"><button type="button" class="linkish" data-market-fill="{{ $c->code }}">↻ Use market rate</button> <span class="muted" data-market-note></span> · Margin: <b data-margin data-sym="{{ $sym }}">{{ $sym }}{{ number_format($c->buy_rate - $c->sell_rate, 2) }}</b> per USDT</p>
      @include('admin.partials.level-limits', ['c' => $c])
      <div class="cur-actions">
        <label class="switch cur-switch"><input type="checkbox" name="is_active" value="1" @checked($open ? old('is_active') : $c->is_active)> Active (clients can trade)</label>
        <div class="cur-actions-r">
          <button type="button" class="btn btn-ghost" data-edit="{{ $c->code }}">Cancel</button>
          <button class="btn btn-primary">Save {{ $c->code }}</button>
        </div>
      </div>
    </form>
  </div>
@empty
  <div class="box" style="text-align:center"><p class="muted">No currencies yet — tap <b>+ Add currency</b>.</p></div>
@endforelse

@php $curMeta = json_encode(config('currency_meta'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); @endphp
<script>
(function () {
  // Add form
  var add = document.getElementById('add');
  function openAdd(on) { add.hidden = !on; if (on) add.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  document.querySelectorAll('[data-open-add]').forEach(function (b) { b.addEventListener('click', function () { openAdd(true); }); });
  document.querySelectorAll('[data-close-add]').forEach(function (b) { b.addEventListener('click', function () { openAdd(false); }); });
  var meta = {!! $curMeta !!};
  var suggestUrl = @json(route('admin.currencies.suggest', ['code' => '__CODE__']));
  function suggest(code) {
    return fetch(suggestUrl.replace('__CODE__', code), { headers: { Accept: 'application/json' }, credentials: 'same-origin' }).then(function (r) { return r.ok ? r.json() : null; });
  }
  function num(v) { return Number(v).toLocaleString(undefined, { maximumFractionDigits: 4 }); }

  // Add form: name/symbol, live market rate → suggested buy/sell, usual payment methods
  var pick = document.querySelector('[data-cur-pick]');
  if (pick) {
    var out = document.querySelector('[data-cur-auto]'), mk = add.querySelector('[data-market]'), pre = add.querySelector('[data-presets]');
    var buy = add.querySelector('[name=buy_rate]'), sell = add.querySelector('[name=sell_rate]'), auto = { buy: '', sell: '' };
    var load = function () {
      var code = pick.value, m = meta[code];
      out.textContent = m ? 'Name: ' + m[0] + ' · Symbol: ' + m[1].trim() : '';
      mk.textContent = 'Checking the market price…';
      suggest(code).then(function (d) {
        if (!d || pick.value !== code) return;
        if (d.rates) {
          mk.innerHTML = 'Market today: <b>1 USDT ≈ ' + num(d.rates.market) + ' ' + code + '</b> · suggested buy +2%, sell −1% (you can change them)';
          if (!buy.value || buy.value === auto.buy) { buy.value = auto.buy = d.rates.buy; }
          if (!sell.value || sell.value === auto.sell) { sell.value = auto.sell = d.rates.sell; }
        } else { mk.textContent = 'Market price not available right now — please enter your rates.'; }
        add.querySelector('[data-receive]').textContent = d.presets.receive.map(function (p) { return p[1]; }).join(', ');
        add.querySelector('[data-pay]').textContent = d.presets.pay.map(function (p) { return p[1]; }).join(', ');
        pre.hidden = false;
      }).catch(function () { mk.textContent = ''; });
    };
    pick.addEventListener('change', load);
    var started = false;
    document.querySelectorAll('[data-open-add]').forEach(function (b) { b.addEventListener('click', function () { if (!started) { started = true; load(); } }); });
    if (!add.hidden) { started = true; load(); }
  }

  // Edit form: "Use market rate"
  document.querySelectorAll('[data-market-fill]').forEach(function (b) {
    b.addEventListener('click', function () {
      var f = b.closest('form'), note = f.querySelector('[data-market-note]');
      note.textContent = 'checking…';
      suggest(b.dataset.marketFill).then(function (d) {
        if (!d || !d.rates) { note.textContent = 'market price not available right now'; return; }
        f.querySelector('[data-buy]').value = d.rates.buy; f.querySelector('[data-sell]').value = d.rates.sell;
        f.querySelector('[data-buy]').dispatchEvent(new Event('input'));
        note.textContent = 'market ' + num(d.rates.market) + ' → buy +2%, sell −1%. Review, then Save.';
      }).catch(function () { note.textContent = ''; });
    });
  });

  // Edit / cancel
  document.querySelectorAll('[data-edit]').forEach(function (b) {
    b.addEventListener('click', function () {
      var f = document.querySelector('[data-edit-form="' + b.dataset.edit + '"]');
      f.hidden = !f.hidden;
      document.querySelectorAll('[data-edit="' + b.dataset.edit + '"]').forEach(function (x) { x.setAttribute('aria-expanded', f.hidden ? 'false' : 'true'); });
      if (!f.hidden) f.querySelector('input:not([type=hidden])').focus();
    });
  });

  // Live margin while typing
  document.querySelectorAll('.cur-edit').forEach(function (f) {
    var buy = f.querySelector('[data-buy]'), sell = f.querySelector('[data-sell]'), m = f.querySelector('[data-margin]');
    function upd() { var v = (parseFloat(buy.value) || 0) - (parseFloat(sell.value) || 0); m.textContent = m.dataset.sym + v.toFixed(2); m.classList.toggle('neg', v < 0); }
    buy.addEventListener('input', upd); sell.addEventListener('input', upd);
  });

  // Links like #cur-INR (from Payment setup) open that currency's edit form; #add opens the add form.
  if (location.hash === '#add') openAdd(true);
  else if (location.hash.indexOf('#cur-') === 0) {
    var card = document.querySelector(location.hash);
    if (card) { card.classList.add('flash-target'); var f = card.querySelector('.cur-edit'); if (f) f.hidden = false; }
  }
})();
</script>
@endsection
