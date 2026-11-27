@extends('layouts.admin')
@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="form" style="max-width:900px">
  @csrf @method('PUT')
  <div class="alert alert-info">USDT rates, order limits, monthly limits and TDS are set per currency in <a class="green" href="{{ route('admin.currencies.index') }}"><b>Currencies &amp; Rates</b></a>.</div>

  <div class="box">
    <div class="box-head"><h3>Trading</h3>
      <label class="switch"><input type="checkbox" name="trading_enabled" value="1" @checked(old('trading_enabled', $s['trading_enabled']))> Trading enabled</label>
    </div>
    <div class="form-grid">
      <div><label>Payment window (minutes)</label><input class="input" type="number" name="order_expiry_minutes" value="{{ old('order_expiry_minutes', $s['order_expiry_minutes']) }}" required></div>
      <div><label>Average completion time (shown to clients)</label><input class="input" name="avg_completion" value="{{ old('avg_completion', $s['avg_completion']) }}" required></div>
      <div class="full"><label>Desk hours</label><input class="input" name="desk_hours" value="{{ old('desk_hours', $s['desk_hours']) }}" required></div>
    </div>
  </div>

  <div class="box">
    <h3 style="margin-bottom:14px">Verification &amp; consent</h3>
    <div class="form-grid">
      <div class="full"><span class="hint">Video verification is now <b>Level 2</b> and EDD is <b>Level 3</b>. Set the monthly limits for each level in <a class="green" href="{{ route('admin.currencies.index') }}">Currencies &amp; Rates</a>.</span></div>
      <div class="full"><label>Verification team names (one per line)</label>
        <textarea class="input" name="kyc_agent_names" rows="5" required>{{ old('kyc_agent_names', $s['kyc_agent_names']) }}</textarea>
        <span class="hint">Shown to clients on the "checking your documents" screen — a different name per document. Use your real team members or the working names they use with clients.</span></div>
      <div><label>Terms version</label><input class="input mono" name="terms_version" value="{{ old('terms_version', $s['terms_version']) }}" required>
        <span class="hint">Change this (e.g. 1.0 → 1.1) after updating the legal pages — every client must accept again before continuing.</span></div>
    </div>
  </div>

  <div class="box">
    <h3 style="margin-bottom:14px">Contact &amp; display</h3>
    <div class="form-grid">
      <div><label>WhatsApp number (with country code, digits only)</label><input class="input" name="support_whatsapp" value="{{ old('support_whatsapp', $s['support_whatsapp']) }}" required></div>
      <div><label>Phone (display)</label><input class="input" name="support_phone" value="{{ old('support_phone', $s['support_phone']) }}" required></div>
      <div><label>Contact email (shown on website)</label><input class="input" type="email" name="support_email" value="{{ old('support_email', $s['support_email']) }}" required></div>
      <div><label>Ticket alerts email (new tickets &amp; client replies)</label><input class="input" type="email" name="ticket_email" value="{{ old('ticket_email', $s['ticket_email']) }}" required>
        <span class="hint">Clients replying to ticket emails also reach this inbox.</span></div>
      <div><label>Instagram URL</label><input class="input" name="instagram_url" value="{{ old('instagram_url', $s['instagram_url']) }}"></div>
      <div class="full"><label>Announcement bar (leave empty to hide)</label><input class="input" name="announcement" value="{{ old('announcement', $s['announcement']) }}" placeholder="e.g. Desk closed on Sunday for maintenance"></div>
    </div>
  </div>
  <button class="btn btn-primary">Save settings</button>
</form>
@endsection
