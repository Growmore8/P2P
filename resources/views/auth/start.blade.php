@extends('layouts.auth')
@section('title', 'Sign up or login')

@section('content')
<h1 class="app-title">Sign up or login</h1>
<form method="POST" action="{{ route('login.identify') }}" class="form" novalidate>
  @csrf
  <div>
    <label>E-mail or Mobile</label>
    <div class="id-row">
      <span data-phone-only hidden>@include('partials.country-picker', ['name' => 'country', 'selected' => $country])</span>
      <input class="input" name="identifier" id="identifier" value="{{ old('identifier') }}" placeholder="Email / Phone (without country code)" autocomplete="username" autofocus required>
    </div>
  </div>
  <button class="btn btn-primary btn-block btn-lg">Continue</button>
  <p class="muted center" style="font-size:12px;margin-top:-4px">See our <a href="{{ route('legal') }}#terms" target="_blank" class="green">Terms</a> and <a href="{{ route('legal') }}#privacy" target="_blank" class="green">Privacy Policy</a>. New accounts accept them on the next step.</p>
</form>
<p class="muted center" style="font-size:13px;margin-top:18px">New here? Enter your email or mobile and we'll create your account.</p>
@endsection

@push('scripts')
<script>
  (function () {
    var inp = document.getElementById('identifier'), wrap = document.querySelector('[data-phone-only]');
    var upd = function () { var v = inp.value.trim(); wrap.hidden = !(v.length && /^[+\d\s\-()]+$/.test(v)); };
    inp.addEventListener('input', upd); upd();
  })();
</script>
@endpush
