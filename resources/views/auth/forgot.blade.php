@extends('layouts.auth')
@section('title', 'Forgot password')

@section('content')
<div class="otp-ico">🔑</div>
<h1 class="app-title center" style="margin-bottom:8px">Forgot password?</h1>
<p class="muted center" style="font-size:14.5px;margin-bottom:20px">Enter your account email. We'll send a 6-digit code from <b style="color:var(--text)">{{ config('mail.from.address') }}</b> to reset your password.</p>
<form method="POST" action="{{ route('password.forgot') }}" class="form">
  @csrf
  <div><label>Email address</label><input class="input" type="email" name="email" value="{{ $email }}" required autofocus autocomplete="email"></div>
  <button class="btn btn-primary btn-block btn-lg">Send code</button>
</form>
<p class="muted center" style="font-size:13.5px;margin-top:18px">Remembered it? <a href="{{ route('login.restart') }}" class="green">Back to login</a></p>
@endsection
