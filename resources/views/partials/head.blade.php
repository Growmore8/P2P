<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'SRM Fintech Solutions') | SRM Fintech Solutions</title>
@php
  $metaTitle = trim(View::yieldContent('title', 'SRM Fintech Solutions')) . ' | SRM Fintech Solutions';
  $metaDesc = trim(View::yieldContent('description', 'SRM Fintech Solutions — KYC-verified P2P desk to buy & sell USDT with local payment methods. Trusted local payment methods; more regions coming soon.'));
@endphp
<meta name="description" content="{{ $metaDesc }}">
<link rel="canonical" href="{{ url()->current() }}">
{{-- Link previews (WhatsApp, Facebook, Telegram, X, LinkedIn) --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="SRM Fintech Solutions">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ asset('images/og-image.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="SRM Fintech Solutions — Buy & Sell USDT P2P">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDesc }}">
<meta name="twitter:image" content="{{ asset('images/og-image.png') }}">
<meta name="theme-color" content="#1f56e0">
<script>try{document.documentElement.dataset.theme=localStorage.getItem('srm-theme')||'light'}catch(e){document.documentElement.dataset.theme='light'}</script>
<link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v=33">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v=33">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}?v=33">
