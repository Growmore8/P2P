@extends('layouts.site')
@section('title', 'Market Insights')

@section('content')
<div class="page-hero">
  <div class="container">
    <p class="crumb"><a href="{{ route('home') }}">Home</a> / Market Insights</p>
    <h1>Understanding global financial markets</h1>
    <p>Market commentary, technical research and educational content covering major global instruments.</p>
  </div>
</div>

<section style="padding-bottom:40px">
  <div class="container">
    <div class="sec-head reveal"><span class="eyebrow">Market coverage</span><h2>What we follow</h2></div>
    <div class="grid-3">
      <div class="card reveal"><div class="icon gold">Au</div><h3>XAUUSD – Gold</h3><p>Price action, key levels and macro drivers of gold.</p></div>
      <div class="card reveal"><div class="icon blue">€$</div><h3>EURUSD &amp; Major FX</h3><p>Euro / US Dollar and other major currency pairs.</p></div>
      <div class="card reveal"><div class="icon">₿</div><h3>BTCUSD &amp; Crypto</h3><p>Bitcoin and selected cryptocurrency markets.</p></div>
    </div>
  </div>
</section>

<section class="section-alt">
  <div class="container">
    <div class="sec-head reveal"><span class="eyebrow">Insights &amp; blog</span><h2>Knowledge. Analysis. Perspectives.</h2></div>
    <div class="filters" id="filters">
      <button class="active" data-f="all">All</button>
      <button data-f="analysis">Market Analysis</button>
      <button data-f="journal">Trade Journal</button>
      <button data-f="research">Technical Research</button>
      <button data-f="updates">Market Updates</button>
      <button data-f="education">Trading Education</button>
      <button data-f="marketing">Digital Marketing</button>
    </div>
    <div class="grid-3">
      <article class="card post reveal" data-cat="analysis">
        <div class="thumb" style="background:linear-gradient(135deg,#3a2c08,#f5b740)">XAUUSD</div>
        <p class="meta">Market Analysis · Coming soon</p><h3 style="margin-top:0">Gold weekly outlook</h3><p>Technical breakdown of key support, resistance and market structure.</p>
      </article>
      <article class="card post reveal" data-cat="journal">
        <div class="thumb" style="background:linear-gradient(135deg,#0b2a4d,#3b82f6)">Journal</div>
        <p class="meta">Trade Journal · Coming soon</p><h3 style="margin-top:0">Completed trade reviews</h3><p>Documented observations from completed trades — what worked and what didn't.</p>
      </article>
      <article class="card post reveal" data-cat="research">
        <div class="thumb" style="background:linear-gradient(135deg,#06331f,#16c784)">S / R</div>
        <p class="meta">Technical Research · Coming soon</p><h3 style="margin-top:0">Support, resistance &amp; price action</h3><p>How market structure and price action shape decision-making.</p>
      </article>
      <article class="card post reveal" data-cat="updates">
        <div class="thumb" style="background:linear-gradient(135deg,#2a1240,#a855f7)">Macro</div>
        <p class="meta">Market Updates · Coming soon</p><h3 style="margin-top:0">Economic calendar highlights</h3><p>Important economic events and global market developments to watch.</p>
      </article>
      <article class="card post reveal" data-cat="education">
        <div class="thumb" style="background:linear-gradient(135deg,#3d0d14,#ef4f5f)">Risk</div>
        <p class="meta">Trading Education · Coming soon</p><h3 style="margin-top:0">Capital protection &amp; risk management</h3><p>Position sizing, stop losses and trading discipline fundamentals.</p>
      </article>
      <article class="card post reveal" data-cat="marketing">
        <div class="thumb" style="background:linear-gradient(135deg,#0e3436,#5eead4)">Meta Ads</div>
        <p class="meta">Digital Marketing · Coming soon</p><h3 style="margin-top:0">Meta advertising updates</h3><p>Practical notes on Facebook &amp; Instagram campaigns and lead generation.</p>
      </article>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="notice reveal"><b>Disclaimer:</b> All market content is for general informational and educational purposes only and does not constitute investment advice, a financial recommendation or a guarantee of returns. Past performance does not guarantee future results. Trading forex, commodities and cryptocurrencies involves substantial risk.</div>
  </div>
</section>
@endsection
