@php
  $srmJs = [
    'pricing' => $pricing ?? \App\Services\Pricing::config(),
    'tradeUrl' => route('trade'),
    'hours' => $settings['desk_hours'],
    'completion' => $settings['avg_completion'],
  ];
@endphp
<script>window.SRM = {!! json_encode($srmJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!};</script>
<script src="{{ asset('js/site.js') }}?v=18"></script>
