@extends('layouts.app')
@section('title', 'Checking your documents')

@section('content')
@php
  $checks = $kyc->checks ?? [];
  $initials = fn ($n) => collect(explode(' ', $n))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('');
@endphp
<div class="kv-scan" aria-hidden="true">
  <div class="kv-ring"></div><div class="kv-ring r2"></div>
  <div class="kv-core" id="kvCore">🛡</div>
</div>
<h1 class="app-title center" style="margin-bottom:6px" id="kvTitle">Checking your documents</h1>
<p class="muted center" style="font-size:14px" id="kvSub">Please keep this page open — this takes a few seconds.</p>

<div class="kv-progress"><span id="kvBar"></span></div>

<ul class="kv-list" id="kvList">
  @foreach ($checks as $i => $c)
    <li class="kv-item" data-ok="{{ $c['ok'] ? 1 : 0 }}">
      <span class="kv-avatar" title="{{ $c['agent'] }}">{{ $initials($c['agent']) }}</span>
      <div class="kv-body">
        <b>{{ $c['label'] }}</b>
        <small class="kv-wait">{{ $c['agent'] }} is checking this document…</small>
        <small class="kv-done">{{ $c['note'] }} · checked by {{ $c['agent'] }}</small>
      </div>
      <span class="kv-state"><span class="kv-spin"></span><span class="kv-tick">{{ $c['ok'] ? '✓' : '!' }}</span></span>
    </li>
  @endforeach
</ul>

<div class="kv-final" id="kvFinal" hidden>
  <div class="o-state-ico ok" style="width:56px;height:56px;font-size:24px">✓</div>
  <h3>Automatic checks complete</h3>
  <p>Your documents are now <b>queued for final review</b> by our verification team. We'll update your account within working hours ({{ $settings['desk_hours'] }}).</p>
  @if (collect($checks)->contains('ok', false))
    <p class="kv-warn">Some files look low quality. If the reviewer can't read them, we'll ask you to upload a clearer copy.</p>
  @endif
  <a href="{{ route('account.limits') }}" class="btn btn-primary btn-block btn-lg" style="margin-top:14px">Continue</a>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var items = Array.prototype.slice.call(document.querySelectorAll('.kv-item'));
  var bar = document.getElementById('kvBar'), fin = document.getElementById('kvFinal');
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var STEP = reduce ? 0 : 1700, i = 0;
  function done() {
    document.getElementById('kvTitle').textContent = 'Documents submitted';
    document.getElementById('kvSub').textContent = 'Automatic checks passed. Final review is next.';
    document.getElementById('kvCore').textContent = '✓';
    document.querySelector('.kv-scan').classList.add('is-done');
    fin.hidden = false; fin.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'nearest' });
  }
  function next() {
    if (i > 0) { var prev = items[i - 1]; prev.classList.remove('active'); prev.classList.add(prev.dataset.ok === '1' ? 'ok' : 'warn'); }
    bar.style.width = Math.round(i / items.length * 100) + '%';
    if (i >= items.length) { setTimeout(done, reduce ? 0 : 500); return; }
    items[i].classList.add('active', 'shown');
    i++;
    setTimeout(next, STEP);
  }
  setTimeout(next, reduce ? 0 : 400);
})();
</script>
@endpush
