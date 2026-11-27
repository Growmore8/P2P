<!doctype html>
<html lang="en">
<head>
@include('partials.head')
<meta name="robots" content="noindex">
</head>
<body>
<div class="panel">
  <aside class="side" id="side">
    @include('partials.logo')
    <nav>@yield('nav')</nav>
    <div class="side-foot">
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button class="btn btn-ghost btn-block btn-sm">Log out</button>
      </form>
    </div>
  </aside>
  <div class="main">
    @if ($settings['announcement'])<div class="announce">{{ $settings['announcement'] }}</div>@endif
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="side-toggle" id="sideToggle" aria-label="Menu">☰</button>
        <h1>@yield('heading')</h1>
      </div>
      <div class="who">
        @yield('topActions')
        <span>{{ auth()->user()->name }}</span>
        <div class="avatar-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
      </div>
    </div>
    <div class="content">
      @include('partials.flash')
      @yield('content')
    </div>
  </div>
</div>
@stack('modals')
@include('partials.srm-js')
@stack('scripts')
</body>
</html>
