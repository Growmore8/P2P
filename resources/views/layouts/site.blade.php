<!doctype html>
<html lang="en">
<head>
@include('partials.head')
</head>
<body>
@if ($settings['announcement'])
  <div class="announce">{{ $settings['announcement'] }}</div>
@endif
<div class="ticker"><div class="ticker-track" id="tickerTrack"></div></div>
<header class="header">
  <nav class="container nav">
    @include('partials.logo')
    <ul class="nav-links" id="navLinks">
      @foreach (['home' => 'Home', 'p2p' => 'P2P USDT', 'services' => 'Services', 'insights' => 'Market Insights', 'about' => 'About', 'contact' => 'Contact'] as $r => $t)
        <li><a href="{{ route($r) }}" class="{{ request()->routeIs($r) ? 'active' : '' }} {{ $r === 'p2p' ? 'p2p-link' : '' }}">{{ $t }}</a></li>
      @endforeach
    </ul>
    <div class="nav-cta">
      @auth
        <a href="{{ auth()->user()->is_admin ? route('admin.dashboard') : route('dashboard') }}" class="btn btn-primary btn-sm">{{ auth()->user()->is_admin ? 'Admin panel' : 'Dashboard' }}</a>
      @else
        <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Log in</a>
        <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Sign up</a>
      @endauth
      @include('partials.theme-toggle')
      <button class="burger" id="burger" aria-label="Menu">☰</button>
    </div>
  </nav>
</header>

@yield('content')

<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        @include('partials.logo')
        <p class="muted" style="margin-top:16px;font-size:14px;max-width:340px">Empowering Digital Growth. Connecting Global Markets.</p>
        <p class="muted" style="margin-top:10px;font-size:13px">Digital Marketing · Trading &amp; Market Insights · Digital Asset Services</p>
        <div class="socials">
          <a href="https://wa.me/{{ $settings['support_whatsapp'] }}" target="_blank" rel="noopener" aria-label="WhatsApp">WA</a>
          <a href="{{ $settings['instagram_url'] }}" target="_blank" rel="noopener" aria-label="Instagram">IG</a>
          <a href="mailto:{{ $settings['support_email'] }}" aria-label="Email">@</a>
        </div>
      </div>
      <div><h5>P2P Desk</h5><ul>
        <li><a href="{{ route('trade', ['side' => 'buy']) }}">Buy USDT</a></li>
        <li><a href="{{ route('trade', ['side' => 'sell']) }}">Sell USDT</a></li>
        <li><a href="{{ route('p2p') }}#rates">Rates &amp; Limits</a></li>
        <li><a href="{{ route('p2p') }}#kyc">KYC Requirements</a></li>
        <li><a href="{{ route('p2p') }}#faq">P2P FAQ</a></li>
      </ul></div>
      <div><h5>Quick Links</h5><ul>
        <li><a href="{{ route('about') }}">About Us</a></li>
        <li><a href="{{ route('services') }}">Our Services</a></li>
        <li><a href="{{ route('insights') }}">Market Insights</a></li>
        <li><a href="{{ route('contact') }}">Contact Us</a></li>
        <li><a href="{{ route('login') }}">Customer Login</a></li>
      </ul></div>
      <div><h5>Legal</h5><ul>
        <li><a href="{{ route('legal') }}#privacy">Privacy Policy</a></li>
        <li><a href="{{ route('legal') }}#terms">Terms &amp; Conditions</a></li>
        <li><a href="{{ route('legal') }}#p2p-terms">P2P Transaction Terms</a></li>
        <li><a href="{{ route('legal') }}#risk">Risk Disclosure</a></li>
        <li><a href="{{ route('legal') }}#disclaimer">Disclaimers</a></li>
      </ul></div>
    </div>
    <div class="footer-bottom">
      <p><b>Risk Disclaimer:</b> Financial markets, forex, commodities and cryptocurrency involve substantial risk. Content on this website is for general information only and is not investment advice. Digital asset services are subject to applicable Indian laws, eligibility and customer due diligence. SRM Fintech Solutions does not claim to be an RBI-authorised bank, stock exchange, cryptocurrency exchange or investment adviser.</p>
      <p>© {{ date('Y') }} SRM Fintech Solutions. All Rights Reserved.</p>
    </div>
  </div>
</footer>
@include('partials.chat-widget')
@stack('modals')
@include('partials.srm-js')
@stack('scripts')
</body>
</html>
