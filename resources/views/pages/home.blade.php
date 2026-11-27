@extends('layouts.site')
@section('title', 'Buy & Sell USDT P2P')

@section('content')
@php $c = $pricing['currencies'][$pricing['currency']] ?? ['code' => 'INR', 'symbol' => '₹', 'buy_rate' => 0, 'sell_rate' => 0, 'min' => 0, 'max' => 0, 'tds' => 0]; @endphp
<section class="hero">
  <div class="container hero-grid">
    <div>
      <h1><span>Buy &amp; sell USDT</span><br>the simple, verified way</h1>
      <p class="lead">Buy and sell USDT with local payment methods — clear rates, KYC-verified customers, live order tracking and real human support. Pay and get paid with trusted local methods; more regions coming soon.</p>
      <div class="hero-actions">
        <a href="{{ route('trade', ['side' => 'buy']) }}" class="btn btn-primary">Buy USDT →</a>
        <a href="{{ route('trade', ['side' => 'sell']) }}" class="btn btn-ghost">Sell USDT</a>
      </div>
      <div class="trust-row">
        <div><b>{{ $c['symbol'] }}{{ number_format($c['buy_rate'], 2) }}</b>Buy rate / USDT</div>
        <div><b>{{ $c['symbol'] }}{{ number_format($c['sell_rate'], 2) }}</b>Sell rate / USDT</div>
        <div><b>{{ $settings['avg_completion'] }}</b>Avg. completion</div>
      </div>
    </div>
    <div>
      @if ($region && ! $region['served'])
        <div class="region-note"><img src="https://flagcdn.com/w40/{{ strtolower($region['code']) }}.png" alt="" width="22" height="22">
          <div><b>Currently not available in your region</b><small>You can still create an account and verify — we'll notify you as soon as trading opens in your region.</small></div></div>
      @endif
      @include('partials.calc', ['mode' => 'widget'])
      @if ($activity->isNotEmpty())
        <div class="activity" id="activityFeed" aria-live="polite" aria-label="Recent completed orders" data-src="{{ route('activity.feed') }}">
          @foreach ($activity->take(3) as $a)
            <div class="activity-item">
              <img src="https://flagcdn.com/w40/{{ $a['cc'] }}.png" alt="">
              <div><b class="act-nick">{{ $a['nick'] }}</b> from {{ $a['country'] }} {{ $a['verb'] }} <b>{{ $a['usdt'] }} USDT</b><small data-ts="{{ $a['ts'] }}">{{ $a['ago'] }}</small></div>
            </div>
          @endforeach
        </div>
        <script>window.SRM_ACTIVITY = {!! json_encode($activity, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};</script>
      @endif
    </div>
  </div>
</section>

<section class="section-alt">
  <div class="container">
    <div class="sec-head center reveal">
      <span class="eyebrow">P2P Digital Asset Desk</span>
      <h2>Why trade USDT with SRM?</h2>
      <p>You see the rate, the fee and the final amount before you commit — and track every order live in your dashboard.</p>
    </div>
    <div class="grid-4">
      <div class="card reveal"><div class="icon">₹</div><h3>Local money in, USDT out</h3><p>In India: pay via UPI, IMPS, NEFT/RTGS or bank transfer — more currencies coming soon. Receive USDT on {{ collect($pricing['networks'])->pluck('code')->join(', ') }}.</p></div>
      <div class="card reveal"><div class="icon blue">🛡</div><h3>KYC-verified only</h3><p>Every customer is verified. Own-account payments only — no third-party funds.</p></div>
      <div class="card reveal"><div class="icon gold">⚡</div><h3>Fast settlement</h3><p>Most orders complete in {{ $settings['avg_completion'] }} once payment is verified.</p></div>
      <div class="card reveal"><div class="icon">📍</div><h3>Live order tracking</h3><p>Follow each step — payment, verification and delivery — from your dashboard.</p></div>
    </div>
  </div>
</section>

@include('partials.brand-strip')

<section>
  <div class="container">
    <div class="sec-head reveal"><span class="eyebrow">How it works</span><h2>Four steps. No guesswork.</h2></div>
    <div class="steps">
      <div class="step reveal"><h4>Sign up &amp; KYC</h4><p>Create an account and upload your PAN, Aadhaar and selfie. Our team verifies it.</p></div>
      <div class="step reveal"><h4>Place an order</h4><p>Choose Buy or Sell, the amount and network. The rate is locked for your order.</p></div>
      <div class="step reveal"><h4>Pay / Send</h4><p>Pay INR to our listed deposit method, or send USDT to our wallet — then submit the UTR / tx hash.</p></div>
      <div class="step reveal"><h4>Receive</h4><p>We verify and release USDT to your wallet, or INR to your bank / UPI.</p></div>
    </div>
    <div class="center" style="margin-top:40px"><a href="{{ route('login') }}" class="btn btn-primary">Create free account</a></div>
  </div>
</section>

<section class="section-alt">
  <div class="container">
    <div class="sec-head reveal">
      <span class="eyebrow">Our core divisions</span>
      <h2>Empowering businesses through digital marketing and global market insights.</h2>
      <p>SRM Fintech Solutions brings together technology, market knowledge and digital expertise to deliver practical solutions for individuals and businesses.</p>
    </div>
    <div class="grid-3">
      <div class="card featured reveal"><span class="tag">Primary</span><div class="icon" style="margin-top:14px">₮</div><h3>Digital Asset Services</h3><p>Eligible P2P USDT buy &amp; sell transactions and support, subject to applicable laws and customer verification.</p><a href="{{ route('p2p') }}" class="btn btn-primary btn-sm" style="margin-top:20px">Go to P2P desk</a></div>
      <div class="card reveal"><div class="icon blue">📈</div><h3>Trading &amp; Market Insights</h3><p>Educational analysis covering Forex, Gold (XAUUSD), Commodities and Cryptocurrency markets.</p><a href="{{ route('insights') }}" class="btn btn-ghost btn-sm" style="margin-top:20px">View insights</a></div>
      <div class="card reveal"><div class="icon gold">📣</div><h3>Digital Marketing Services</h3><p>Meta Ads, Facebook &amp; Instagram advertising and lead generation campaigns.</p><a href="{{ route('services') }}#marketing" class="btn btn-ghost btn-sm" style="margin-top:20px">Explore marketing</a></div>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="sec-head center reveal"><span class="eyebrow">Why choose SRM Fintech Solutions</span><h2>Our commitment to professional excellence</h2></div>
    <div class="grid-3">
      <div class="card reveal"><h3>Professional Approach</h3><p>A structured and professional approach to every service we deliver.</p></div>
      <div class="card reveal"><h3>Transparency</h3><p>Clear communication, transparent service terms and responsible practices.</p></div>
      <div class="card reveal"><h3>Digital Expertise</h3><p>Focused on digital advertising, online marketing and financial market knowledge.</p></div>
      <div class="card reveal"><h3>Customer Focus</h3><p>We take time to understand your requirements and give relevant information.</p></div>
      <div class="card reveal"><h3>Responsible Practices</h3><p>We value legal requirements, customer verification and responsible delivery.</p></div>
      <div class="card reveal"><h3>Continuous Improvement</h3><p>We keep learning and refining how we serve our customers.</p></div>
    </div>
  </div>
</section>

<section style="padding-top:0">
  <div class="container">
    <div class="cta-band reveal">
      <div><h2>Ready to buy or sell USDT?</h2><p class="muted">Sign up in a minute, complete KYC and place your first order.</p></div>
      <div style="display:flex;gap:12px;flex-wrap:wrap"><a href="{{ route('login') }}" class="btn btn-primary">Get started</a><a href="https://wa.me/{{ $settings['support_whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-ghost">WhatsApp us</a></div>
    </div>
  </div>
</section>
@endsection
