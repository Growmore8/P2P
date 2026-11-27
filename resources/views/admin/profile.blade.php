@extends('layouts.admin')
@section('title', 'My account')
@section('heading', 'My account')

@section('content')
<div class="split" style="max-width:1000px">
  <form method="POST" action="{{ route('admin.profile.update') }}" class="box form">
    @csrf @method('PUT')
    <h3>Login email &amp; name</h3>
    <p class="muted" style="font-size:13.5px;margin-top:-6px">This email is used to log in to the admin panel and to receive password-reset codes.</p>
    <div><label>Name</label><input class="input" name="name" value="{{ old('name', $u->name) }}" required></div>
    <div><label>Admin email</label><input class="input" type="email" name="email" value="{{ old('email', $u->email) }}" required autocomplete="email"></div>
    <div><label>Current password (to confirm)</label><input class="input" type="password" name="current_password" required autocomplete="current-password"></div>
    <button class="btn btn-primary">Save</button>
  </form>

  <form method="POST" action="{{ route('admin.profile.password') }}" class="box form">
    @csrf @method('PUT')
    <h3>Change password</h3>
    <div><label>Current password</label><input class="input" type="password" name="current_password" required autocomplete="current-password"></div>
    <div><label>New password</label><input class="input" type="password" name="password" required autocomplete="new-password"></div>
    <div><label>Confirm new password</label><input class="input" type="password" name="password_confirmation" required autocomplete="new-password"></div>
    <span class="hint">At least 8 characters with letters and numbers. Other logged-in devices will be signed out.</span>
    <button class="btn btn-primary">Change password</button>
  </form>
</div>
<div class="box" style="max-width:1000px">
  <dl class="kv" style="grid-template-columns:140px 1fr">
    <dt>Last login</dt><dd>{{ $u->last_login_at?->format('d M Y, h:i A') ?? '—' }}</dd>
    <dt>Account created</dt><dd>{{ $u->created_at->format('d M Y') }}</dd>
  </dl>
  <p class="muted" style="font-size:12.5px;margin-top:10px">Changes to your email and password are recorded in <a class="green" href="{{ route('admin.history') }}">History</a>.</p>
</div>
@endsection
