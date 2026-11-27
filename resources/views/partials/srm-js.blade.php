@php
  $srmJs = [
    'pricing' => $pricing ?? \App\Services\Pricing::config(),
    'tradeUrl' => route('trade'),
    'hours' => $settings['desk_hours'],
    'completion' => $settings['avg_completion'],
  ];
@endphp
<script>window.SRM = {!! json_encode($srmJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!};</script>
@include('partials.dialog')
<script src="{{ asset('js/site.js') }}?v=19"></script>
<script>
  // Register the service worker so the site can be installed as an app.
  if ('serviceWorker' in navigator && (location.protocol === 'https:' || ['127.0.0.1', 'localhost'].indexOf(location.hostname) !== -1)) {
    window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
  }
</script>
