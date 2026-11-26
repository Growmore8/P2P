@extends('layouts.auth')
@section('title', $title)

@section('content')
<div class="otp-ico">✉</div>
<h1 class="app-title center" style="margin-bottom:8px">{{ $title }}</h1>
<p class="muted center" style="font-size:14.5px">{{ $intro }}<br><b style="color:var(--text);word-break:break-all">{{ $email }}</b>
  @if ($change) · <a href="{{ $change }}" class="green">Change</a>@endif</p>

@if ($devCode)
  <div class="alert alert-info center" style="margin-top:14px">🧪 Local test mode (no mail server yet) — your code is <b class="mono" style="letter-spacing:3px">{{ $devCode }}</b></div>
@endif

<form method="POST" action="{{ $action }}" class="form" id="otpForm" style="margin-top:18px">
  @csrf
  <input type="hidden" name="code" id="otpValue">
  <div class="otp-boxes" role="group" aria-label="6-digit code">
    @for ($i = 0; $i < 6; $i++)
      <input class="otp-box" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i + 1 }}" @if ($i === 0) autofocus @endif>
    @endfor
  </div>
  <button class="btn btn-primary btn-block btn-lg" id="otpSubmit" disabled>Verify</button>
</form>

<form method="POST" action="{{ $resend }}" class="center" style="margin-top:16px">
  @csrf
  <span class="muted" style="font-size:14px">Didn't get it? Check spam, or</span>
  <button class="linkish" id="resendBtn" data-wait="{{ $cooldown }}" @disabled($cooldown > 0)>Resend code</button>
  <span class="muted" id="resendTimer" style="font-size:14px"></span>
</form>
<p class="muted center" style="font-size:12px;margin-top:18px">The code expires in {{ \App\Models\OtpCode::TTL_MINUTES }} minutes. Never share it with anyone.</p>
@endsection

@push('scripts')
<script>
(function () {
  var boxes = Array.prototype.slice.call(document.querySelectorAll('.otp-box'));
  var hidden = document.getElementById('otpValue'), submit = document.getElementById('otpSubmit'), form = document.getElementById('otpForm');
  function sync() {
    var v = boxes.map(function (b) { return b.value; }).join('');
    hidden.value = v; submit.disabled = v.length !== 6;
    boxes.forEach(function (b) { b.classList.toggle('filled', !!b.value); });
    if (v.length === 6 && !form.dataset.sent) { form.dataset.sent = '1'; form.submit(); }
  }
  function fill(start, digits) {
    digits.split('').forEach(function (d, k) { if (boxes[start + k]) boxes[start + k].value = d; });
    var next = Math.min(start + digits.length, 5); boxes[next].focus(); sync();
  }
  boxes.forEach(function (b, i) {
    b.addEventListener('input', function () {
      var d = b.value.replace(/\D/g, '');
      if (d.length > 1) { fill(i, d.slice(0, 6 - i)); return; }
      b.value = d; if (d && i < 5) boxes[i + 1].focus(); sync();
    });
    b.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace' && !b.value && i > 0) { boxes[i - 1].focus(); boxes[i - 1].value = ''; sync(); e.preventDefault(); }
      if (e.key === 'ArrowLeft' && i > 0) boxes[i - 1].focus();
      if (e.key === 'ArrowRight' && i < 5) boxes[i + 1].focus();
    });
    b.addEventListener('paste', function (e) {
      var t = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
      if (t) { e.preventDefault(); fill(0, t); }
    });
    b.addEventListener('focus', function () { b.select(); });
  });
  @if ($errors->has('code')) boxes.forEach(function (b) { b.classList.add('err-box'); }); @endif

  // resend countdown
  var btn = document.getElementById('resendBtn'), timer = document.getElementById('resendTimer');
  var left = parseInt(btn.dataset.wait, 10) || 0;
  function tick() {
    if (left > 0) { btn.disabled = true; timer.textContent = 'in ' + left + 's'; left--; setTimeout(tick, 1000); }
    else { btn.disabled = false; timer.textContent = ''; }
  }
  tick();
})();
</script>
@endpush
