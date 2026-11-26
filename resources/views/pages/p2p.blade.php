@extends('layouts.site')
@section('title', 'P2P USDT Desk — Buy & Sell USDT with INR')

@section('content')
@php $c = $pricing['currencies'][$pricing['currency']] ?? ['code' => 'INR', 'symbol' => '₹', 'buy_rate' => 0, 'sell_rate' => 0, 'min' => 0, 'max' => 0, 'tds' => 0]; @endphp
<section class="hero" id="trade">
  <div class="container hero-grid">
    <div>
      <p class="crumb"><a href="{{ route('home') }}">Home</a> / P2P USDT Desk</p>
      <span class="pill" style="margin-top:14px"><span class="dot"></span> Desk rate · updated by our team</span>
      <h1>P2P <span>USDT ⇄ INR</span> Desk</h1>
      <p class="lead">Buy USDT with Indian Rupees or sell USDT for INR directly with SRM Fintech Solutions. Exact rate, fee and final amount shown upfront.</p>
      <div class="grid-2" style="margin-top:30px;gap:14px">
        <div class="card" style="padding:20px"><p class="muted" style="font-size:13px">We sell at (you buy)</p><h3 class="green" style="margin:6px 0 0;font-size:28px">{{ $c['symbol'] }}{{ number_format($c['buy_rate'], 2) }}</h3></div>
        <div class="card" style="padding:20px"><p class="muted" style="font-size:13px">We buy at (you sell)</p><h3 class="red" style="margin:6px 0 0;font-size:28px">{{ $c['symbol'] }}{{ number_format($c['sell_rate'], 2) }}</h3></div>
      </div>
      <div class="trust-row" style="margin-top:26px">
        <div><b>{{ $c['symbol'] }}{{ number_format($c['min']) }}</b>Minimum order</div>
        <div><b>{{ $c['symbol'] }}{{ number_format($c['max']) }}</b>Maximum / order</div>
        <div><b>{{ $settings['avg_completion'] }}</b>Avg. completion</div>
      </div>
    </div>
    @include('partials.calc', ['mode' => 'widget'])
  </div>
</section>

<section class="section-alt" id="rates">
  <div class="container">
    <div class="sec-head reveal"><span class="eyebrow">Rates &amp; limits</span><h2>Today's USDT rates</h2><p>The rate is locked when you place an order. Network fees are charged on buy orders.</p></div>
    <div class="table-wrap reveal">
      <table>
        <thead><tr><th>Asset / Network</th><th>Buy rate</th><th>Sell rate</th><th>Network fee</th><th>Order limit</th><th>Status</th></tr></thead>
        <tbody>
          @foreach ($networks as $n)
            <tr>
              <td><b>USDT</b> <span class="muted">· {{ $n->name }}</span></td>
              <td class="green"><b>{{ $c['symbol'] }}{{ number_format($c['buy_rate'], 2) }}</b></td>
              <td class="red"><b>{{ $c['symbol'] }}{{ number_format($c['sell_rate'], 2) }}</b></td>
              <td>{{ $n->fee }} USDT</td>
              <td>{{ $c['symbol'] }}{{ number_format($c['min']) }} – {{ $c['symbol'] }}{{ number_format($c['max']) }}</td>
              <td>
                @if ($n->buy_enabled)<span class="badge ok">Buy</span>@endif
                @if ($n->sell_enabled && $n->deposit_address)<span class="badge warn">Sell</span>@endif
                @if (! $n->buy_enabled && ! ($n->sell_enabled && $n->deposit_address))<span class="badge bad">Paused</span>@endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="grid-3" style="margin-top:22px">
      <div class="card reveal"><div class="icon">₹</div><h3>Payment methods</h3><ul><li>UPI</li><li>IMPS</li><li>NEFT / RTGS</li><li>Bank transfer</li></ul></div>
      <div class="card reveal"><div class="icon blue">⛓</div><h3>Supported networks</h3><ul>@foreach ($networks as $n)<li>{{ $n->name }}</li>@endforeach</ul></div>
      <div class="card reveal"><div class="icon gold">⏱</div><h3>Desk hours</h3><p>{{ $settings['desk_hours'] }}</p><p style="margin-top:10px">Orders placed outside hours are processed in the next working session.</p></div>
    </div>
  </div>
</section>

<section id="how">
  <div class="container">
    <div class="grid-2" style="gap:50px;align-items:start">
      <div class="reveal">
        <span class="eyebrow green">Buying USDT</span>
        <h2 style="font-size:32px;margin:10px 0 24px">How to buy USDT</h2>
        <div class="steps" style="grid-template-columns:1fr">
          <div class="step"><h4>Place a buy order</h4><p>Enter INR amount, network and your wallet address. Choose a deposit method.</p></div>
          <div class="step"><h4>Pay from your own account</h4><p>Pay the exact amount to the deposit details shown on your order page within the time limit.</p></div>
          <div class="step"><h4>Submit UTR &amp; screenshot</h4><p>Enter the UTR / reference number and upload the payment screenshot.</p></div>
          <div class="step"><h4>Receive USDT</h4><p>After verification we send USDT to your wallet and share the transaction hash.</p></div>
        </div>
      </div>
      <div class="reveal">
        <span class="eyebrow red">Selling USDT</span>
        <h2 style="font-size:32px;margin:10px 0 24px">How to sell USDT</h2>
        <div class="steps" style="grid-template-columns:1fr">
          <div class="step"><h4>Place a sell order</h4><p>Enter USDT amount and network, and choose your saved bank / UPI payout account.</p></div>
          <div class="step"><h4>Send USDT</h4><p>Send USDT to the wallet address shown on your order page — on the correct network only.</p></div>
          <div class="step"><h4>Submit transaction hash</h4><p>Paste the blockchain transaction hash so we can verify receipt.</p></div>
          <div class="step"><h4>Receive INR</h4><p>INR is credited to your own account (net of applicable TDS) and the UTR shared.</p></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-alt" id="kyc">
  <div class="container">
    <div class="grid-2" style="gap:50px;align-items:center">
      <div class="reveal">
        <span class="eyebrow">KYC &amp; compliance</span>
        <h2 style="font-size:clamp(28px,3.4vw,40px);margin:10px 0 16px">Verified customers. Clean funds. Clear records.</h2>
        <p class="muted">We follow customer due diligence procedures before processing any order. All services are subject to applicable Indian laws, eligibility and operational availability.</p>
        <div class="notice" style="margin-top:24px"><b>Tax note:</b> Transfers of virtual digital assets in India may attract {{ $c['tds'] ?: 1 }}% TDS under Section 194S and tax on gains under Section 115BBH of the Income-tax Act. Where applicable, TDS is deducted on sell orders and shown in your quote.</div>
      </div>
      <div class="card reveal">
        <h3 style="margin-top:0">What you'll need</h3>
        <ul><li>PAN card (mandatory)</li><li>Aadhaar card (front &amp; back)</li><li>Selfie holding your PAN card</li><li>Bank account / UPI in your own name</li><li>Your own crypto wallet address</li></ul>
        <h3>What we don't accept</h3>
        <ul class="no"><li>Third-party payments or payouts</li><li>Cash deposits</li><li>Funds from unverified sources</li><li>Customers below 18 years of age</li></ul>
      </div>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="sec-head center reveal"><span class="eyebrow">Stay safe</span><h2>Protect yourself from scams</h2></div>
    <div class="grid-4">
      <div class="card reveal"><div class="icon">☎</div><h3>Official details only</h3><p>Only pay to the deposit details shown inside your logged-in order page.</p></div>
      <div class="card reveal"><div class="icon blue">🔑</div><h3>Never share keys</h3><p>We never ask for your seed phrase, private key, OTP or UPI PIN.</p></div>
      <div class="card reveal"><div class="icon gold">⚠</div><h3>No "guaranteed returns"</h3><p>We never offer investment schemes or fixed returns.</p></div>
      <div class="card reveal"><div class="icon">✔</div><h3>Check the network</h3><p>Always double-check the wallet address and network before sending USDT.</p></div>
    </div>
  </div>
</section>

<section class="section-alt" id="faq">
  <div class="container">
    <div class="sec-head center reveal"><span class="eyebrow">FAQ</span><h2>P2P questions, answered</h2></div>
    <div class="faq reveal">
      <details><summary>What is P2P USDT trading?</summary><p>You transact directly with SRM Fintech Solutions' merchant desk instead of an exchange order book. You pay INR and receive USDT, or send USDT and receive INR.</p></details>
      <details><summary>How long do I have to pay?</summary><p>Each buy order has a payment window of {{ $settings['order_expiry_minutes'] }} minutes. Unpaid orders expire automatically — just place a new order.</p></details>
      <details><summary>How long does verification take?</summary><p>KYC is usually reviewed within working hours. Orders typically complete in {{ $settings['avg_completion'] }} after payment is verified.</p></details>
      <details><summary>Why is TDS deducted on sell orders?</summary><p>Under Section 194S of the Income-tax Act, 1% TDS may apply on the transfer of virtual digital assets. Where applicable we deduct it and it is shown in your quote.</p></details>
      <details><summary>Can someone else pay on my behalf?</summary><p>No. Payments and payouts must be from / to accounts in the verified customer's own name. Third-party payments are refunded to source and the order rejected.</p></details>
      <details><summary>What if my order is rejected?</summary><p>The rejection reason is shown on your order page. If you had already paid, our team will contact you for a refund to the source account.</p></details>
      <details><summary>Is SRM Fintech Solutions an exchange?</summary><p>No. SRM Fintech Solutions is a sole proprietorship offering eligible digital asset transaction services. We do not claim to be an RBI-authorised bank, exchange or investment adviser.</p></details>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="cta-band reveal">
      <div><h2>Need a bulk / OTC quote?</h2><p class="muted">For orders above {{ $c['symbol'] }}{{ number_format($c['max']) }}, talk to our desk directly.</p></div>
      <a href="https://wa.me/{{ $settings['support_whatsapp'] }}?text={{ rawurlencode('Hi, I need a bulk / OTC quote for USDT.') }}" target="_blank" rel="noopener" class="btn btn-primary">Request OTC quote</a>
    </div>
  </div>
</section>
@endsection
