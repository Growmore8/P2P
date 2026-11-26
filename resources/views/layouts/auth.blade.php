<!doctype html>
<html lang="en">
<head>
@include('partials.head')
</head>
<body class="auth-body">
<div class="auth-stage">
  <div class="app-card">
    <div class="app-card-top">
      <a href="{{ route('home') }}" class="icon-btn" aria-label="Close">✕</a>
      @include('partials.logo')
      @include('partials.theme-toggle')
    </div>
    <div class="app-card-body">
      @include('partials.flash')
      @yield('content')
    </div>
    <div class="app-card-foot">
      <span>🔒 Secure &amp; Private</span>
      <span>KYC-verified desk</span>
    </div>
  </div>
  <p class="auth-legal"><a href="{{ route('legal') }}#terms">Terms &amp; Conditions</a> · <a href="{{ route('legal') }}#privacy">Privacy Policy</a></p>
</div>
@include('partials.srm-js')
@stack('scripts')
</body>
</html>
