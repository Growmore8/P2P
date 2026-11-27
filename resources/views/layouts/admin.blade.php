@extends('layouts.panel')

@section('nav')
  @php $side = request('side'); $isOrders = request()->routeIs('admin.orders.*'); $mgr = auth()->user()->isManager(); $rv = request('review'); @endphp
  @if ($mgr)
    {{-- Manager menu: orders to review + own account --}}
    <div class="nav-title">Review payments</div>
    <a href="{{ route('admin.orders.index', ['review' => 'waiting']) }}" class="{{ $isOrders && $rv === 'waiting' ? 'active' : '' }}"><span class="ico">⏳</span> Waiting for review
      @if ($badge['review'] ?? 0)<span class="count">{{ $badge['review'] }}</span>@endif</a>
    <a href="{{ route('admin.orders.index', ['side' => 'buy']) }}" class="{{ $isOrders && $side === 'buy' && ! $rv ? 'active' : '' }}"><span class="ico">⇣</span> Buy Orders</a>
    <a href="{{ route('admin.orders.index', ['side' => 'sell']) }}" class="{{ $isOrders && $side === 'sell' && ! $rv ? 'active' : '' }}"><span class="ico">⇡</span> Sell Orders</a>
    <a href="{{ route('admin.orders.index') }}" class="{{ $isOrders && ! $side && ! $rv ? 'active' : '' }}"><span class="ico">⇄</span> All Orders</a>
    <div class="nav-title">Clients</div>
    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><span class="ico">👥</span> Client history</a>
    <div class="nav-title">Rates</div>
    <a href="{{ route('admin.rates') }}" class="{{ request()->routeIs('admin.rates') ? 'active' : '' }}"><span class="ico">💱</span> Buy &amp; sell rates</a>
    <div class="nav-title">Account</div>
    <a href="{{ route('admin.profile') }}" class="{{ request()->routeIs('admin.profile') ? 'active' : '' }}"><span class="ico">🔐</span> My account</a>
  @else
  <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span class="ico">◧</span> Dashboard</a>

  <div class="nav-title">Transactions</div>
  <a href="{{ route('admin.orders.index', ['side' => 'buy']) }}" class="{{ $isOrders && $side === 'buy' ? 'active' : '' }}"><span class="ico">⇣</span> Buy Orders</a>
  <a href="{{ route('admin.orders.index', ['side' => 'sell']) }}" class="{{ $isOrders && $side === 'sell' ? 'active' : '' }}"><span class="ico">⇡</span> Sell Orders</a>
  <a href="{{ route('admin.orders.index') }}" class="{{ $isOrders && ! $side ? 'active' : '' }}"><span class="ico">⇄</span> All Orders
    @if ($badge['orders'])<span class="count">{{ $badge['orders'] }}</span>@endif</a>

  <div class="nav-title">Clients</div>
  <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><span class="ico">👥</span> Clients</a>
  <a href="{{ route('admin.chats.index') }}" class="{{ request()->routeIs('admin.chats.*') ? 'active' : '' }}"><span class="ico">💬</span> Live Chat
    @if ($badge['chats'])<span class="count">{{ $badge['chats'] }}</span>@endif</a>
  <a href="{{ route('admin.tickets.index') }}" class="{{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}"><span class="ico">🎧</span> Support Tickets
    @if ($badge['tickets'])<span class="count">{{ $badge['tickets'] }}</span>@endif</a>
  <a href="{{ route('admin.kyc.index') }}" class="{{ request()->routeIs('admin.kyc.*') ? 'active' : '' }}"><span class="ico">🛡</span> KYC Verification
    @if ($badge['kyc'])<span class="count">{{ $badge['kyc'] }}</span>@endif</a>

  <div class="nav-title">Payments</div>
  <a href="{{ route('admin.payments') }}" class="{{ request()->routeIs('admin.payments') ? 'active' : '' }}"><span class="ico">✅</span> Payment setup</a>
  <a href="{{ route('admin.rates') }}" class="{{ request()->routeIs('admin.rates') ? 'active' : '' }}"><span class="ico">⚡</span> Quick rates</a>
  <a href="{{ route('admin.currencies.index') }}" class="{{ request()->routeIs('admin.currencies.*') ? 'active' : '' }}"><span class="ico">💱</span> Rates &amp; currencies</a>
  <a href="{{ route('admin.methods.index') }}" class="{{ request()->routeIs('admin.methods.*') ? 'active' : '' }}"><span class="ico">⬇</span> How clients pay you</a>
  <a href="{{ route('admin.payout-methods.index') }}" class="{{ request()->routeIs('admin.payout-methods.*') ? 'active' : '' }}"><span class="ico">⬆</span> How you pay clients</a>
  <a href="{{ route('admin.networks.index') }}" class="{{ request()->routeIs('admin.networks.*') ? 'active' : '' }}"><span class="ico">⛓</span> Your USDT wallets</a>

  <div class="nav-title">System</div>
  <a href="{{ route('admin.managers.index') }}" class="{{ request()->routeIs('admin.managers.*') ? 'active' : '' }}"><span class="ico">🧑‍💼</span> Managers</a>
  <a href="{{ route('admin.history') }}" class="{{ request()->routeIs('admin.history') ? 'active' : '' }}"><span class="ico">🕘</span> History</a>
  <a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}"><span class="ico">⚙</span> Settings</a>
  <a href="{{ route('admin.profile') }}" class="{{ request()->routeIs('admin.profile') ? 'active' : '' }}"><span class="ico">🔐</span> My account</a>
  <a href="{{ route('home') }}" target="_blank"><span class="ico">⌂</span> View website</a>
  @endif
@endsection

@section('topActions')
  @include('partials.theme-toggle')
@endsection
