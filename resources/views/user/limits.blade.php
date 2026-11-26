@extends('layouts.app')
@section('title', 'Verification and Limits')
@section('back', route('account'))
@section('heading', 'Verification and Limits')

@section('content')
@php $st = $user->kyc_status; @endphp
<div class="hero-icon">🪪</div>

<div class="box" style="text-align:center">
  @if ($st === 'approved')
    <span class="badge ok">✓ Verified</span>
    @if ($kyc)<p class="muted" style="margin-top:8px;font-size:14px">{{ $kyc->idTypeLabel() }}@if ($kyc->reviewed_at) · verified {{ $kyc->reviewed_at->format('d M Y') }}@endif</p>@endif
  @elseif ($st === 'pending')
    <span class="badge warn">In review</span>
    <p class="muted" style="margin-top:8px;font-size:14px">Submitted {{ $kyc?->created_at->diffForHumans() }}. We'll update you within working hours.</p>
  @else
    @if ($st === 'rejected')
      <div class="alert alert-error" style="text-align:left">Previous verification rejected: {{ $kyc?->reject_reason }}</div>
    @endif
    <a href="{{ route('kyc.show') }}" class="btn btn-primary btn-block btn-lg">Verify Now</a>
    <p class="muted" style="margin-top:10px;font-size:13px">Takes about 3 minutes. Keep your ID ready.</p>
  @endif
</div>

<p class="section-label" style="margin-top:6px">Monthly limits · {{ $code }}</p>
@if (! $limits)
  <div class="alert alert-info">Trading in {{ $code }} ({{ $user->countryName() }}) isn't available yet. You can still verify your account — we'll notify you when it opens.</div>
@else
<div class="box">
  @foreach (['buy' => 'Buy', 'sell' => 'Sell'] as $k => $label)
    @php $l = $limits[$k]; $pct = $l['limit'] > 0 ? min(100, $l['used'] / $l['limit'] * 100) : 0; @endphp
    <div class="srow">
      <div class="sbody">
        <b>{{ $label }}</b>
        <small>{{ money($l['used'], $code, 0) }} used of {{ $l['limit'] > 0 ? money($l['limit'], $code, 0) : 'no limit' }} · resets on the 1st</small>
        @if ($l['limit'] > 0)<div class="meter"><span style="width:{{ $pct }}%"></span></div>@endif
      </div>
    </div>
  @endforeach
</div>
@endif
<p class="muted" style="font-size:12.5px">@if ($currency)Per order: {{ money($currency->min_amount, $currency->code, 0) }} – {{ money($currency->max_amount, $currency->code, 0) }}.@endif Need higher limits? <a class="green" href="https://wa.me/{{ $settings['support_whatsapp'] }}" target="_blank" rel="noopener">Contact us</a>.</p>
@endsection
