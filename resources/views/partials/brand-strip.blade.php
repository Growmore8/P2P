{{-- Scrolling logo strip: wallets clients receive USDT in + payment methods we accept. --}}
@php
  $chip = function ($b) {
      $black = strtolower($b['color']) === '#000000';
      $style = '--bc:' . ($black ? 'var(--text)' : $b['color']);
      $cls = 'bchip' . ($black ? ' bk' : '');
      if ($b['logo'] && str_ends_with($b['logo'], '.svg') && ($svg = \App\Support\BrandStrip::inlineSvg($b['logo']))) {
          $icon = $svg;
      } elseif ($b['logo']) {
          $icon = '<img src="' . e(asset($b['logo'])) . '" alt="" loading="lazy">';
      } else {
          $icon = '<i class="binit">' . e(mb_strtoupper(mb_substr($b['name'], 0, 1))) . '</i>';
      }
      return '<span class="' . $cls . '" style="' . e($style) . '">' . $icon . '<span>' . e($b['name']) . '</span></span>';
  };
  // repeat short lists so each scrolling row is wider than the screen
  $row = function (array $items) use ($chip) {
      $times = max(1, (int) ceil(14 / max(1, count($items))));
      $html = '';
      for ($t = 0; $t < $times; $t++) foreach ($items as $b) $html .= $chip($b);
      return $html;
  };
  $wallets = $row($strip['wallets']);
  $payments = count($strip['payments']) ? $row($strip['payments']) : '';
@endphp
<section class="brand-strip" aria-label="Supported wallets and payment methods">
  <div class="container">
    <div class="sec-head center reveal" style="margin-bottom:30px">
      <span class="eyebrow">Works with your favourite apps</span>
      <h2>Receive USDT in any wallet. Pay the way you like.</h2>
    </div>
  </div>

  <p class="marquee-label">Receive USDT in</p>
  <div class="marquee" aria-label="Wallets and exchanges">
    <div class="marquee-track">
      <div class="marquee-group">{!! $wallets !!}</div>
      <div class="marquee-group" aria-hidden="true">{!! $wallets !!}</div>
    </div>
  </div>

  @if ($payments)
    <p class="marquee-label">Pay with</p>
    <div class="marquee reverse" aria-label="Payment methods">
      <div class="marquee-track">
        <div class="marquee-group">{!! $payments !!}</div>
        <div class="marquee-group" aria-hidden="true">{!! $payments !!}</div>
      </div>
    </div>
  @endif

  <div class="container">
    <p class="brand-note">Send to any wallet or exchange that supports USDT on {{ \App\Models\Network::ordered()->pluck('code')->join(', ', ' or ') }} — always pick the matching network.
      Brand names and logos belong to their owners; SRM Fintech Solutions is not affiliated with them.</p>
  </div>
</section>
