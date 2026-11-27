@extends('layouts.admin')
@section('title', 'Payment setup')
@section('heading', 'Payment setup')

@section('content')
<p class="muted" style="margin:-4px 0 18px;max-width:760px">Everything clients need to buy and sell, in one place. A currency is ready when every required item below has a green tick.</p>

{{-- How it works --}}
<div class="pay-flow">
  <a href="{{ route('admin.currencies.index') }}" class="pay-step"><span class="ps-n">1</span><span class="ps-ico">💱</span><b>Rates &amp; currencies</b><small>Your buy / sell price for USDT and limits</small></a>
  <a href="{{ route('admin.methods.index') }}" class="pay-step"><span class="ps-n">2</span><span class="ps-ico">⬇</span><b>How clients pay you</b><small>UPI, bank, payment link — used when clients <u>buy</u></small></a>
  <a href="{{ route('admin.payout-methods.index') }}" class="pay-step"><span class="ps-n">3</span><span class="ps-ico">⬆</span><b>How you pay clients</b><small>Account types clients add — used when they <u>sell</u></small></a>
  <a href="{{ route('admin.networks.index') }}" class="pay-step"><span class="ps-n">4</span><span class="ps-ico">⛓</span><b>Your USDT wallets</b><small>Where clients send USDT when they <u>sell</u></small></a>
</div>

@foreach ($rows as $r)
  @php $c = $r['c']; @endphp
  <div class="box pay-cur {{ $c->is_active ? '' : 'is-off' }}">
    <div class="box-head">
      <div style="display:flex;gap:12px;align-items:center">
        <span class="pay-code">{{ $c->code }}</span>
        <div><h3 style="font-size:18px">{{ $c->name }}</h3><small class="muted">{{ $r['countries'] }} {{ \Illuminate\Support\Str::plural('country', $r['countries']) }} use this currency</small></div>
      </div>
      <div class="actions">
        @if (! $c->is_active)<span class="badge bad">Switched off</span>@endif
        <span class="badge {{ $r['canBuy'] ? 'ok' : 'warn' }}">{{ $r['canBuy'] ? '✓ Clients can buy' : 'Buying not ready' }}</span>
        <span class="badge {{ $r['canSell'] ? 'ok' : 'warn' }}">{{ $r['canSell'] ? '✓ Clients can sell' : 'Selling not ready' }}</span>
      </div>
    </div>
    <div class="pay-checks">
      @foreach ($r['checks'] as $ch)
        <div class="pay-check {{ $ch['ok'] ? 'ok' : (! empty($ch['optional']) ? 'opt' : 'bad') }}">
          <span class="pc-ico">{{ $ch['ok'] ? '✓' : (! empty($ch['optional']) ? '•' : '!') }}</span>
          <div class="pc-body"><b>{{ $ch['label'] }}</b><small>{{ $ch['detail'] }}</small></div>
          <a href="{{ $ch['fix'] }}" class="btn btn-sm {{ $ch['ok'] ? 'btn-ghost' : 'btn-primary' }}">{{ $ch['ok'] ? 'Edit' : 'Fix' }}</a>
        </div>
      @endforeach
    </div>
    @if (! $c->is_active)
      <p class="muted" style="font-size:13px;margin-top:10px">Clients in these countries see “not available in your region yet”. Switch it on in <a class="green" href="{{ route('admin.currencies.index') }}#cur-{{ $c->code }}">Rates &amp; currencies</a> when everything is ready.</p>
    @endif
  </div>
@endforeach

<div class="box" style="text-align:center">
  <h3>Serve another country?</h3>
  <p class="muted" style="margin:6px 0 14px">Add its currency first (it can stay switched off), then add how clients pay you and how you pay them.</p>
  <a href="{{ route('admin.currencies.index') }}#add" class="btn btn-primary">+ Add a currency</a>
</div>
@endsection
