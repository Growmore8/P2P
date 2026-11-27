@extends('layouts.admin')
@section('title', 'Currencies & Rates')
@section('heading', 'Currencies & Rates')

@section('content')
<div class="alert alert-info">Each currency has its own USDT rates, order limits, <b>monthly limits per verification level</b> (Identity → Video → EDD) and TDS. Clients trade in the currency of their country — a country is open for trading only when its currency is <b>active</b> and it has at least one deposit / receive method.</div>

@foreach ($currencies as $c)
  <form method="POST" action="{{ route('admin.currencies.update', $c) }}" class="box form">
    @csrf @method('PUT')
    <div class="box-head" style="margin-bottom:4px">
      <h3><span class="mono">{{ $c->code }}</span> · {{ $c->name }}</h3>
      <div class="actions">
        <span class="chips">@foreach (array_slice($c->countryCodes(), 0, 6) as $cc)<span class="chip-sm"><img src="https://flagcdn.com/w20/{{ strtolower($cc) }}.png" alt="">{{ $cc }}</span>@endforeach @if (count($c->countryCodes()) > 6)<span class="chip-sm">+{{ count($c->countryCodes()) - 6 }}</span>@endif</span>
        <label class="switch"><input type="checkbox" name="is_active" value="1" @checked($c->is_active)> Active</label>
      </div>
    </div>
    <div class="form-grid-3">
      <div><label>Buy rate — client pays ({{ $c->symbol }} per USDT)</label><input class="input" type="number" step="0.0001" name="buy_rate" value="{{ $c->buy_rate }}" required style="font-size:18px;font-weight:700"></div>
      <div><label>Sell rate — client receives ({{ $c->symbol }} per USDT)</label><input class="input" type="number" step="0.0001" name="sell_rate" value="{{ $c->sell_rate }}" required style="font-size:18px;font-weight:700"></div>
      <div><label>TDS on sell orders (%)</label><input class="input" type="number" step="0.01" name="tds_percent" value="{{ $c->tds_percent }}" required></div>
      <div><label>Min per order</label><input class="input" type="number" step="0.01" name="min_amount" value="{{ $c->min_amount }}" required></div>
      <div><label>Max per order (0 = none)</label><input class="input" type="number" step="0.01" name="max_amount" value="{{ $c->max_amount }}" required></div>
      <div><label>Symbol / Name</label><div style="display:flex;gap:6px"><input class="input" name="symbol" value="{{ $c->symbol }}" style="max-width:70px" required><input class="input" name="name" value="{{ $c->name }}" required></div></div>
      <div><label>Sort</label><input class="input" type="number" name="sort" value="{{ $c->sort }}"></div>
    </div>
    @include('admin.partials.level-limits', ['c' => $c])
    <div class="actions"><button class="btn btn-primary btn-sm">Save {{ $c->code }}</button>
      <span class="muted" style="font-size:12.5px;align-self:center">Spread: {{ $c->symbol }}{{ number_format($c->buy_rate - $c->sell_rate, 2) }} per USDT</span></div>
  </form>
@endforeach

<form method="POST" action="{{ route('admin.currencies.store') }}" class="box form">
  @csrf
  <h3>Add currency</h3>
  <div class="form-grid-3">
    <div><label>Currency</label>
      <select class="input" name="code" required>
        @foreach ($available as $code)<option value="{{ $code }}" @selected(old('code') === $code)>{{ $code }} — {{ collect(config('countries'))->filter(fn ($c) => $c[2] === $code)->pluck(0)->join(', ') }}</option>@endforeach
      </select>
    </div>
    <div><label>Name</label><input class="input" name="name" value="{{ old('name') }}" placeholder="Sri Lankan Rupee" required></div>
    <div><label>Symbol</label><input class="input" name="symbol" value="{{ old('symbol') }}" placeholder="Rs " required></div>
    <div><label>Buy rate (per USDT)</label><input class="input" type="number" step="0.0001" name="buy_rate" value="{{ old('buy_rate') }}" required></div>
    <div><label>Sell rate (per USDT)</label><input class="input" type="number" step="0.0001" name="sell_rate" value="{{ old('sell_rate') }}" required></div>
    <div><label>TDS on sell (%)</label><input class="input" type="number" step="0.01" name="tds_percent" value="{{ old('tds_percent', 0) }}" required></div>
    <div><label>Min per order</label><input class="input" type="number" step="0.01" name="min_amount" value="{{ old('min_amount', 0) }}" required></div>
    <div><label>Max per order</label><input class="input" type="number" step="0.01" name="max_amount" value="{{ old('max_amount', 0) }}" required></div>
  </div>
  @include('admin.partials.level-limits', ['c' => null])
  <div class="actions"><label class="switch"><input type="checkbox" name="is_active" value="1" checked> Active</label><button class="btn btn-primary btn-sm">Add currency</button></div>
</form>
@endsection
