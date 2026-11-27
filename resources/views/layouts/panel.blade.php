<!doctype html>
<html lang="en">
<head>
@include('partials.head')
<meta name="robots" content="noindex">
</head>
<body>
<script>
  // Admin pop-ups: [data-modal-open="id"] opens <div class="modal" id="id">, [data-modal-close] / backdrop / Esc closes.
  (function () {
    function open(m, on) {
      if (!m) return;
      m.classList.toggle('open', on); m.setAttribute('aria-hidden', on ? 'false' : 'true');
      document.body.classList.toggle('modal-on', !!document.querySelector('.modal.open'));
      if (on) { var f = m.querySelector('input:not([type=hidden]):not([readonly]), select, textarea'); if (f) setTimeout(function () { f.focus(); }, 60); }
    }
    document.addEventListener('click', function (e) {
      var o = e.target.closest('[data-modal-open]');
      if (o) { e.preventDefault(); open(document.getElementById(o.dataset.modalOpen), true); return; }
      var c = e.target.closest('[data-modal-close]');
      if (c) { open(c.closest('.modal'), false); return; }
      if (e.target.classList && e.target.classList.contains('modal') && e.target.hasAttribute('data-admin-modal')) open(e.target, false);
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') document.querySelectorAll('.modal.open[data-admin-modal]').forEach(function (m) { open(m, false); }); });
    window.srmModal = open;

    // Dangerous actions: <form data-type-confirm="SRM123" data-type-message="..."> asks the admin to type the code first.
    document.addEventListener('submit', function (e) {
      var f = e.target.closest('form[data-type-confirm]'); if (!f) return;
      var code = f.dataset.typeConfirm, typed = window.prompt((f.dataset.typeMessage || 'This cannot be undone.') + '\n\nType ' + code + ' to confirm:');
      if (typed === null || typed.trim().toUpperCase() !== code.toUpperCase()) { e.preventDefault(); if (typed !== null) alert('The code did not match — nothing was deleted.'); return; }
      var h = f.querySelector('input[name=confirm_id]') || f.appendChild(Object.assign(document.createElement('input'), { type: 'hidden', name: 'confirm_id' }));
      h.value = typed.trim();
    }, true);
  })();
</script>

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
