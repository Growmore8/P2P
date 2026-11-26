@extends('layouts.site')
@section('title', 'Contact Us')

@section('content')
<div class="page-hero">
  <div class="container">
    <p class="crumb"><a href="{{ route('home') }}">Home</a> / Contact</p>
    <h1>Let's connect. Let's explore opportunities.</h1>
    <p>Whether you're looking to buy or sell USDT, need digital marketing services or want market insights — we welcome your enquiry.</p>
  </div>
</div>

<section>
  <div class="container grid-2" style="gap:40px;align-items:start">
    <div class="card reveal">
      <h3 style="margin-top:0">Send an enquiry</h3>
      <p style="margin-bottom:20px">Your enquiry opens in WhatsApp so our team can reply fast.</p>
      <form class="form" id="contactForm" novalidate>
        <div><label>Full name</label><input class="input" name="name" required></div>
        <div class="form-row">
          <div><label>Email address</label><input class="input" type="email" name="email" required></div>
          <div><label>Phone number</label><input class="input" name="phone" inputmode="tel" required></div>
        </div>
        <div><label>Service interested in</label>
          <select name="service">
            <option>P2P USDT – Buy</option><option>P2P USDT – Sell</option><option>Bulk / OTC USDT</option>
            <option>Digital Marketing Services</option><option>Trading &amp; Market Insights</option><option>Other</option>
          </select>
        </div>
        <div><label>Message</label><textarea name="message" required></textarea></div>
        <p id="contactMsg"></p>
        <button class="btn btn-primary btn-block">Submit enquiry</button>
      </form>
    </div>

    <div class="reveal">
      <h3 style="font-size:24px;margin-bottom:22px">Business information</h3>
      <ul class="info-list">
        <li><span class="icon">🏢</span><div><small>Company</small>SRM FINTECH SOLUTIONS</div></li>
        <li><span class="icon blue">✉</span><div><small>Email</small><a href="mailto:{{ $settings['support_email'] }}">{{ $settings['support_email'] }}</a></div></li>
      </ul>
      <div class="notice" style="margin-top:26px"><b>Beware of impersonators.</b> Only pay to the deposit details shown inside your logged-in order page. We never ask for OTPs, UPI PINs, private keys or seed phrases.</div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
document.getElementById('contactForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const f = Object.fromEntries(new FormData(this));
  const out = document.getElementById('contactMsg');
  if (!f.name || !f.email || !f.phone || !f.message) { out.className = 'err'; out.textContent = 'Please fill all fields.'; return; }
  const msg = `*Website Enquiry*\nName: ${f.name}\nEmail: ${f.email}\nPhone: ${f.phone}\nService: ${f.service}\n\n${f.message}`;
  window.open('https://wa.me/{{ $settings['support_whatsapp'] }}?text=' + encodeURIComponent(msg), '_blank', 'noopener');
  out.className = 'green'; out.textContent = 'Your enquiry opened in WhatsApp — press send to reach our team.';
  this.reset();
});
</script>
@endpush
