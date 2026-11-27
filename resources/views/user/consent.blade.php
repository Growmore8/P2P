@extends('layouts.auth')
@section('title', 'Updated terms')

@section('content')
<h1 class="app-title">Please review &amp; accept</h1>
<p class="muted" style="font-size:14px;margin:-10px 0 18px">Our agreements have been updated (version {{ $version }}). Please read and accept them to continue using your account.</p>
<form method="POST" action="{{ route('consent.store') }}" class="form">
  @csrf
  <div class="box" style="background:var(--card-2);border:none;padding:16px;border-radius:14px">
    <label class="check"><input type="checkbox" name="terms" value="1" required> <span>I have read and agree to the <a href="{{ route('legal') }}#terms" target="_blank" class="green">Terms &amp; Conditions</a>, <a href="{{ route('legal') }}#p2p-terms" target="_blank" class="green">P2P Transaction Terms</a> and <a href="{{ route('legal') }}#privacy" target="_blank" class="green">Privacy Policy</a>.</span></label>
    <label class="check" style="margin-top:12px"><input type="checkbox" name="risk" value="1" required> <span>I have read the <a href="{{ route('legal') }}#risk" target="_blank" class="green">Risk Disclosure</a> and understand that digital assets carry risk and that SRM Fintech Solutions does not give investment advice.</span></label>
    <label class="check" style="margin-top:12px"><input type="checkbox" name="age" value="1" required> I am at least 18 years old and I will trade only for myself, using accounts in my own name.</label>
  </div>
  <button class="btn btn-primary btn-block btn-lg">I agree — continue</button>
</form>
<form method="POST" action="{{ route('logout') }}" style="margin-top:12px">@csrf<button class="btn btn-ghost btn-block">Log out</button></form>
<p class="muted center" style="font-size:12px;margin-top:14px">Your acceptance is recorded with the date, time and IP address.</p>
@endsection
