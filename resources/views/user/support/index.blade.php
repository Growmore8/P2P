@extends('layouts.app')
@section('title', 'Support')
@section('back', route('account'))
@section('heading', 'Support')

@section('content')
<div class="sup-hero">
  <div class="hero-icon" style="margin:0 0 10px">🎧</div>
  <h3>How can we help?</h3>
  <p class="muted">Raise a ticket and our team will reply by email and here in your account.</p>
  <a href="{{ route('support.create') }}" class="btn btn-primary btn-block btn-lg" style="margin-top:14px">+ Raise a ticket</a>
</div>

<p class="section-label" style="margin-top:18px">Common topics</p>
<div class="sup-topics">
  @foreach (array_slice(\App\Models\Ticket::CATEGORIES, 0, 6, true) as $k => [$label, $icon])
    <a href="{{ route('support.create', ['category' => $k]) }}" class="sup-topic"><span>{{ $icon }}</span>{{ $label }}</a>
  @endforeach
</div>

<p class="section-label" style="margin-top:20px">My tickets · {{ $tickets->count() }}</p>
@forelse ($tickets as $t)
  <a href="{{ route('support.show', $t) }}" class="lrow">
    <span class="pr-ico" style="width:38px;height:38px;border-radius:50%;display:grid;place-items:center;background:var(--card)">{{ $t->categoryIcon() }}</span>
    <div class="lmain"><b>{{ $t->subject }}</b><small class="mono">{{ $t->ticket_no }}</small> <small>· {{ ($t->last_reply_at ?? $t->created_at)->diffForHumans() }}</small></div>
    <div class="lend"><span class="badge {{ $t->statusClass() }}">{{ $t->status === 'answered' ? 'Reply received' : $t->statusLabel() }}</span></div>
  </a>
@empty
  <div class="empty-card"><b>No tickets yet</b><p>When you raise a ticket, it will appear here.</p></div>
@endforelse

<div class="sup-contact">
  <a href="https://wa.me/{{ $settings['support_whatsapp'] }}" target="_blank" rel="noopener" class="lrow"><span>💬</span><div class="lmain"><b>WhatsApp</b><small>Quick questions during desk hours</small></div><span class="pr-caret">›</span></a>
  <a href="mailto:{{ $settings['support_email'] }}" class="lrow"><span>✉</span><div class="lmain"><b>Email</b><small>{{ $settings['support_email'] }}</small></div><span class="pr-caret">›</span></a>
</div>
@endsection
