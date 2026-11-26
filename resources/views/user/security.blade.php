@extends('layouts.app')
@section('title', 'Security')
@section('back', route('account'))
@section('heading', 'Security')

@section('content')
<div class="hero-icon">🛡</div>
<div class="box">
  <div class="srow">
    <span class="si">🔑</span>
    <div class="sbody">
      <b>Password</b><small>Your password to sign in</small>
      <button type="button" class="linkish" data-toggle="pwForm" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}">Change Password</button>
    </div>
    <span class="badge ok" style="align-self:flex-start">Enabled</span>
  </div>
  <form method="POST" action="{{ route('account.password') }}" class="form" id="pwForm" @if (! $errors->any()) hidden @endif style="padding-bottom:16px">
    @csrf @method('PUT')
    <div><label>Current password</label><input class="input" type="password" name="current_password" required autocomplete="current-password"></div>
    <div><label>New password</label><input class="input" type="password" name="password" required autocomplete="new-password"></div>
    <div><label>Confirm new password</label><input class="input" type="password" name="password_confirmation" required autocomplete="new-password"></div>
    <button class="btn btn-primary btn-block">Update password</button>
  </form>
  <div class="srow">
    <span class="si">✉</span>
    <div class="sbody"><b>E-mail</b><small>{{ $user->email }}</small></div>
    <span class="badge ok" style="align-self:flex-start">Enabled</span>
  </div>
  <div class="srow">
    <span class="si">📱</span>
    <div class="sbody"><b>Mobile</b><small>{{ $user->phone }}</small></div>
    <span class="badge ok" style="align-self:flex-start">Enabled</span>
  </div>
  <div class="srow">
    <span class="si">🔐</span>
    <div class="sbody"><b>Authenticator app</b><small>Extra login protection with a 6-digit code</small></div>
    <span class="badge warn" style="align-self:flex-start">Coming soon</span>
  </div>
</div>
<p class="muted" style="font-size:12.5px">Last login: {{ $user->last_login_at?->format('d M Y, h:i A') ?? '—' }}. We will never ask for your password, OTP or wallet keys.</p>
@endsection
