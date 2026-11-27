@extends('layouts.admin')
@section('title', 'Rates & currencies')
@section('heading', 'Rates & currencies')

@section('content')
<p class="muted" style="margin:-4px 0 14px"><a class="green" href="{{ route('admin.payments') }}">← Payment setup</a> · Set how much clients pay (buy) and receive (sell) per USDT in each currency.</p>
<div class="alert alert-info">Each currency has its own USDT rates, order limits, <b>monthly limits per verification level</b> (Identity → Video → EDD) and TDS. Clients trade in the currency of their country — a country is open for trading only when its currency is <b>active</b> and it has at least one deposit / receive method.</div>

@foreach ($currencies as $c)
  <form method="POST" action="{{ route('admin.currencies.update', $c) }}" class="box form" id="cur-{{ $c->code }}">
    @csrf @method('PUT')
    <div class="box-head" style="margin-bottom:4px">
      <h3><span class="mono">{{ $c->code }}</span> · {{ $c->name }} <span class="muted" style="font-weight:600">({{ trim($c->symbol) }})</span></h3>
      <div class="actions">
        <span class="chips">@foreach (array_slice($c->countryCodes(), 0, 6) as $cc)<span class="chip-sm"><img src="https://flagcdn.com/w20/{{ strtolower($cc) }}.png" alt="">{{ $cc }}</span>@endforeach @if (count($c->countryCodes()) > 6)<span class="chip-sm">+{{ count($c->countryCodes()) - 6 }}</span>@endif</span>
        <label class="switch cur-switch"><input type="checkbox" name="is_active" value="1" @checked($c->is_active)> Active</label>
      </div>
    </div>
    <div class="form-grid-3">
      <div><label>Buy rate — client pays ({{ $c->symbol }} per USDT)</label><input class="input" type="number" step="0.0001" name="buy_rate" value="{{ $c->buy_rate }}" required style="font-size:18px;font-weight:700"></div>
      <div><label>Sell rate — client receives ({{ $c->symbol }} per USDT)</label><input class="input" type="number" step="0.0001" name="sell_rate" value="{{ $c->sell_rate }}" required style="font-size:18px;font-weight:700"></div>
      <div><label>TDS on sell orders (%)</label><input class="input" type="number" step="0.01" name="tds_percent" value="{{ $c->tds_percent }}" required></div>
      <div><label>Min per order</label><input class="input" type="number" step="0.01" name="min_amount" value="{{ $c->min_amount }}" required></div>
      <div><label>Max per order (0 = none)</label><input class="input" type="number" step="0.01" name="max_amount" value="{{ $c->max_amount }}" required></div>
    </div>
    @include('admin.partials.level-limits', ['c' => $c])
    <div class="actions"><button class="btn btn-primary btn-sm">Save {{ $c->code }}</button>
      <span class="muted" style="font-size:12.5px;align-self:center">Spread: {{ $c->symbol }}{{ number_format($c->buy_rate - $c->sell_rate, 2) }} per USDT</span></div>
  </form>
@endforeach

<form method="POST" action="{{ route('admin.currencies.store') }}" class="box form" id="add">
  @csrf
  <h3>Add currency</h3>
  <p class="muted" style="font-size:13px;margin:4px 0 12px">Pick the currency — its name and symbol are set automatically. You can keep it switched off until its payment methods are ready.</p>
  <div class="form-grid-3">
    <div><label>Currency</label>
      <select class="input" name="code" required data-cur-pick>
        @foreach ($available as $code)<option value="{{ $code }}" @selected(old('code') === $code)>{{ $code }} · {{ config('currency_meta.' . $code . '.0', $code) }} — {{ collect(config('countries'))->filter(fn ($c) => $c[2] === $code)->pluck(0)->join(', ') }}</option>@endforeach
      </select>
    </div>
    <div><label>Name &amp; symbol</label><div class="input cur-auto" data-cur-auto>—</div><span class="hint">Filled in automatically</span></div>
    <div><label>Buy rate (per USDT)</label><input class="input" type="number" step="0.0001" name="buy_rate" value="{{ old('buy_rate') }}" required></div>
    <div><label>Sell rate (per USDT)</label><input class="input" type="number" step="0.0001" name="sell_rate" value="{{ old('sell_rate') }}" required></div>
    <div><label>TDS on sell (%)</label><input class="input" type="number" step="0.01" name="tds_percent" value="{{ old('tds_percent', 0) }}" required></div>
    <div><label>Min per order</label><input class="input" type="number" step="0.01" name="min_amount" value="{{ old('min_amount', 0) }}" required></div>
    <div><label>Max per order</label><input class="input" type="number" step="0.01" name="max_amount" value="{{ old('max_amount', 0) }}" required></div>
  </div>
  @include('admin.partials.level-limits', ['c' => null])
  <div class="actions" style="align-items:center"><label class="switch cur-switch"><input type="checkbox" name="is_active" value="1" checked> Active</label><button class="btn btn-primary btn-sm">Add currency</button></div>
</form>
@php $curMeta = json_encode(config('currency_meta'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); @endphp
<script>
(function () {
  var meta = {!! $curMeta !!};
  var pick = document.querySelector('[data-cur-pick]'), out = document.querySelector('[data-cur-auto]');
  if (pick && out) {
    var show = function () { var m = meta[pick.value]; out.textContent = m ? m[0] + '  (' + m[1].trim() + ')' : pick.value; };
    pick.addEventListener('change', show); show();
  }
  if (location.hash) { var t = document.querySelector(location.hash); if (t) t.classList.add('flash-target'); }
})();
</script>
@endsection
