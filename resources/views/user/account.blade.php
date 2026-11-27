@extends('layouts.app')
@section('title', 'Your Profile')

@section('content')
@php $wa = $settings['support_whatsapp']; @endphp
<div class="profile-hero">
  <div class="avatar-lg">{{ strtoupper(substr($user->name, 0, 1)) }}
    <img src="https://flagcdn.com/w40/{{ strtolower($user->country) }}.png" alt="{{ $user->countryName() }}">
  </div>
  <div style="min-width:0">
    <h2>{{ $user->name }}</h2>
    <p class="cid-line">Client ID: <b class="mono">{{ $user->client_id }}</b></p>
    <p>{{ $user->email }}</p>
    <p>{{ $user->phone }}</p>
  </div>
</div>

<a class="menu-item" href="{{ route('account.limits') }}"><span class="mi">🪪</span> Verification &amp; Limits
  @if ($user->kyc_status !== 'approved')<span class="badge warn" style="margin-left:auto">{{ $user->kyc_status === 'pending' ? 'In review' : 'Verify' }}</span>@else<span class="mchev">›</span>@endif</a>
<a class="menu-item" href="{{ route('accounts.index') }}"><span class="mi">🏦</span> Bank Accounts<span class="mchev">›</span></a>
<a class="menu-item" href="{{ route('account.security') }}"><span class="mi">🛡</span> Security<span class="mchev">›</span></a>
<a class="menu-item" href="{{ route('orders.index') }}"><span class="mi">🕘</span> Order History<span class="mchev">›</span></a>
<a class="menu-item" href="{{ route('support.index') }}"><span class="mi">🎧</span> Support &amp; tickets<span class="mchev">›</span></a>
<div class="menu-sep"></div>
<button type="button" class="menu-item" data-toggle="feedbackMenu" aria-expanded="false"><span class="mi">💬</span> Feedback<span class="mchev">⌄</span></button>
<div class="sub-menu" id="feedbackMenu" hidden>
  <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Feedback on buying USDT with SRM: ') }}" target="_blank" rel="noopener"><span class="si">✎</span><span>Tell us how buying went<small>1-minute message</small></span></a>
  <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Feedback on selling USDT with SRM: ') }}" target="_blank" rel="noopener"><span class="si">✎</span><span>Tell us how selling went<small>1-minute message</small></span></a>
  <a href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Hi, I would like to talk to the SRM team.') }}" target="_blank" rel="noopener"><span class="si">🎧</span><span>Talk to our team<small>WhatsApp chat</small></span></a>
</div>
<form method="POST" action="{{ route('logout') }}">@csrf<button class="menu-item"><span class="mi">⇥</span> Logout</button></form>
@endsection
