@extends('layouts.auth')
@section('title', ! empty($auth['admin']) ? 'Admin login' : 'Enter password')

@section('content')
@if (! empty($auth['admin']))
  <div class="admin-login-badge">🛡 Admin panel</div>
  <h1 class="app-title">Admin login</h1>
@else
  <h1 class="app-title">Welcome back</h1>
@endif
<div class="id-chip">
  <span>{{ $auth['value'] }}</span>
  <a href="{{ route('login.restart') }}" class="green">Change</a>
</div>
<form method="POST" action="{{ route('login') }}" class="form">
  @csrf
  <div><label>Password</label><input class="input" type="password" name="password" required autofocus autocomplete="current-password"></div>
  @if (empty($auth['admin']))
    <label class="check"><input type="checkbox" name="remember" value="1"> Keep me logged in on this device</label>
  @endif
  <button class="btn btn-primary btn-block btn-lg">{{ ! empty($auth['admin']) ? 'Log in to admin panel' : 'Log in' }}</button>
</form>
<p class="center" style="font-size:14px;margin-top:18px"><a href="{{ route('password.forgot') }}" class="green" style="font-weight:700">Forgot password?</a></p>
@if (! empty($auth['admin']))
  <p class="muted center" style="font-size:12.5px;margin-top:8px">Authorised staff only. Login attempts are rate-limited and logged.</p>
@endif
@endsection
