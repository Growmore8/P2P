<!doctype html>
<html lang="en">
<head>
@include('partials.head')
<meta name="robots" content="noindex">
</head>
<body class="auth-body">
@php $wa = $settings['support_whatsapp']; @endphp
<div class="auth-stage">
  <div class="app-card">
    {{-- Drawer menu --}}
    <nav class="drawer" id="drawer" aria-label="Menu">
      <div class="drawer-top">
        <button type="button" class="icon-btn" data-drawer-close aria-label="Close menu">✕</button>
        @include('partials.theme-toggle')
      </div>
      <a class="menu-item" href="{{ route('trade', ['side' => 'buy']) }}"><span class="mi">⇣</span> Buy</a>
      <a class="menu-item" href="{{ route('trade', ['side' => 'sell']) }}"><span class="mi">⇡</span> Sell</a>
      <div class="menu-sep"></div>
      <a class="menu-item" href="{{ route('account') }}"><span class="mi">👤</span> Account</a>
      <a class="menu-item" href="{{ route('orders.index') }}"><span class="mi">🕘</span> Order History</a>
      <div class="menu-sep"></div>
      <a class="menu-item" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><span class="mi">🎧</span> Support</a>
      <a class="menu-item" href="{{ route('home') }}"><span class="mi">⌂</span> Website</a>
      <form method="POST" action="{{ route('logout') }}">@csrf<button class="menu-item"><span class="mi">⇥</span> Logout</button></form>
      <div class="drawer-foot"><a href="{{ route('legal') }}#terms">Terms &amp; Conditions</a> · <a href="{{ route('legal') }}#privacy">Privacy Policy</a></div>
    </nav>

    <div class="app-card-top">
      <button type="button" class="icon-btn" data-drawer-open aria-label="Open menu">☰</button>
      @include('partials.logo')
      <div class="right">
        <a class="icon-btn" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" aria-label="Support" title="Support">🎧</a>
      </div>
    </div>

    <div class="app-card-body">
      @hasSection('back')
        <div class="app-head">
          <a href="@yield('back')" class="icon-btn" aria-label="Back">‹</a>
          <h2>@yield('heading')</h2>
        </div>
      @endif
      @include('partials.flash')
      @yield('content')
    </div>

    @yield('sheets')
  </div>
  <p class="auth-legal">🛡 SRM Fintech Solutions · KYC-verified P2P desk</p>
</div>
@include('partials.srm-js')
@stack('scripts')
</body>
</html>
