@extends('layouts.auth')
@section('title', 'Create account')

@section('content')
@php
  $auth = $auth ?? null;
  $byEmail = ($auth['type'] ?? null) === 'email';
  $byPhone = ($auth['type'] ?? null) === 'phone';
  $country = old('country', $auth['country'] ?? ($geoCountry ?? 'IN'));
  $localPhone = $byPhone ? substr($auth['value'], strlen('+' . config("countries.$country.1"))) : '';
@endphp
<h1 class="app-title">Create your account</h1>
@if ($auth)
  <div class="id-chip"><span>{{ $auth['value'] }}</span><a href="{{ route('login.restart') }}" class="green">Change</a></div>
@endif
<form method="POST" action="{{ route('register') }}" class="form">
  @csrf
  <div><label>Full name (as per ID)</label><input class="input" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus></div>

  @if ($byEmail)
    <input type="hidden" name="email" value="{{ $auth['value'] }}">
  @else
    <div><label>Email address</label><input class="input" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></div>
  @endif

  <div>
    <label>Mobile number (WhatsApp)</label>
    <div class="id-row">
      @include('partials.country-picker', ['name' => 'country', 'selected' => $country])
      <input class="input" name="phone" value="{{ old('phone', $localPhone) }}" inputmode="tel" maxlength="16" placeholder="Mobile number" required autocomplete="tel-national" @readonly($byPhone)>
    </div>
    <span class="hint">Your country is also used for verification.</span>
  </div>

  <div class="form-row">
    <div><label>Password</label><input class="input" type="password" name="password" required autocomplete="new-password"></div>
    <div><label>Confirm</label><input class="input" type="password" name="password_confirmation" required autocomplete="new-password"></div>
  </div>
  <span class="hint" style="margin-top:-6px">At least 8 characters with letters and numbers.</span>
  <label class="check"><input type="checkbox" name="terms" value="1" @checked(old('terms', $auth ? 1 : 0))> I agree to the <a href="{{ route('legal') }}#terms" target="_blank" class="green">Terms &amp; Conditions</a>, <a href="{{ route('legal') }}#p2p-terms" target="_blank" class="green">P2P Terms</a> and <a href="{{ route('legal') }}#privacy" target="_blank" class="green">Privacy Policy</a>.</label>
  <label class="check"><input type="checkbox" name="risk" value="1" @checked(old('risk'))> I have read the <a href="{{ route('legal') }}#risk" target="_blank" class="green">Risk Disclosure</a> and understand digital assets carry risk.</label>
  <label class="check"><input type="checkbox" name="age" value="1" @checked(old('age'))> I am 18 or older and will trade only for myself.</label>
  <button class="btn btn-primary btn-block btn-lg">Create account</button>
</form>
<p class="muted center" style="font-size:13px;margin-top:16px">Already have an account? <a href="{{ route('login.restart') }}" class="green">Log in</a></p>
@endsection
