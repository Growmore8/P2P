@extends('layouts.auth')
@section('title', 'New password')

@section('content')
<div class="otp-ico">🔒</div>
<h1 class="app-title center" style="margin-bottom:8px">Set a new password</h1>
<p class="muted center" style="font-size:14.5px;margin-bottom:20px">For <b style="color:var(--text)">{{ $email }}</b></p>
<form method="POST" action="{{ route('password.update') }}" class="form">
  @csrf
  <div><label>New password</label><input class="input" type="password" name="password" required autofocus autocomplete="new-password"></div>
  <div><label>Confirm new password</label><input class="input" type="password" name="password_confirmation" required autocomplete="new-password"></div>
  <span class="hint">At least 8 characters with letters and numbers.</span>
  <button class="btn btn-primary btn-block btn-lg">Save new password</button>
</form>
@endsection
