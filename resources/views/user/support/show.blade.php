@extends('layouts.app')
@section('title', 'Ticket ' . $ticket->ticket_no)
@section('back', route('support.index'))
@section('heading', 'Ticket')

@section('content')
@if (session('ticket_created'))
  <div class="tk-success">
    <div class="o-state-ico ok" style="width:60px;height:60px;font-size:26px">✓</div>
    <h3>Ticket submitted successfully</h3>
    <p>Your ticket ID is</p>
    <div class="tk-id"><span class="mono">{{ $ticket->ticket_no }}</span><button type="button" class="icon-copy" data-copy="{{ $ticket->ticket_no }}" aria-label="Copy ticket ID">⧉</button></div>
    <p>We've emailed a confirmation to <b>{{ auth()->user()->email }}</b>. Our team will review your case and contact you soon.</p>
  </div>
@endif

<div class="o-hero" style="margin-bottom:14px">
  <div class="o-hero-top"><span class="badge {{ $ticket->statusClass() }}">{{ $ticket->status === 'answered' ? 'Reply received' : $ticket->statusLabel() }}</span><span class="badge info">{{ $ticket->categoryIcon() }} {{ $ticket->categoryLabel() }}</span></div>
  <h3 style="font-size:18px;line-height:1.3">{{ $ticket->subject }}</h3>
  <div class="o-meta"><span class="mono">{{ $ticket->ticket_no }}</span><button type="button" class="icon-copy" data-copy="{{ $ticket->ticket_no }}" aria-label="Copy ticket ID">⧉</button><span class="o-dot">•</span><span>{{ $ticket->created_at->format('d M Y, h:i A') }}</span></div>
  @if ($ticket->order)
    <a href="{{ route('orders.show', $ticket->order) }}" class="green" style="font-size:13.5px;font-weight:700;display:inline-block;margin-top:8px">Order {{ $ticket->order->order_no }} →</a>
  @endif
</div>

<div class="thread">
  @foreach ($ticket->messages as $m)
    <div class="bubble {{ $m->is_staff ? 'staff' : 'me' }}">
      <div class="bubble-head">{{ $m->is_staff ? 'SRM Support' : 'You' }} · {{ $m->created_at->format('d M, h:i A') }}</div>
      <div class="bubble-body">{{ $m->body }}</div>
      @if ($m->attachment)
        <a class="bubble-file" href="{{ route('files.ticket', $m) }}" target="_blank" rel="noopener">📎 View attachment</a>
      @endif
    </div>
  @endforeach
</div>

@if ($ticket->isClosed())
  <div class="alert alert-info" style="margin-top:14px">This ticket is closed. Need more help? <a class="green" href="{{ route('support.create') }}"><b>Raise a new ticket</b></a>.</div>
@else
  <form method="POST" action="{{ route('support.reply', $ticket) }}" enctype="multipart/form-data" class="form o-card" style="margin-top:14px">
    @csrf
    <div><label>Add a reply</label><textarea class="input" name="message" rows="3" maxlength="5000" placeholder="Write your message…" required></textarea></div>
    <label class="o-upload" style="padding:10px 12px">
      <input type="file" name="attachment" accept="image/*,.pdf" data-upload>
      <span class="o-upload-ico" style="width:30px;height:30px;font-size:13px">📎</span>
      <span data-upload-label>Attach a file <small>optional · max 5 MB</small></span>
    </label>
    <button class="btn btn-primary btn-block">Send reply</button>
  </form>
  <form method="POST" action="{{ route('support.close', $ticket) }}" class="center" style="margin-top:12px" data-confirm="Close this ticket? You can always open a new one.">
    @csrf <button class="linkish">My issue is solved — close ticket</button>
  </form>
@endif
@endsection

@push('scripts')
<script>
  (function () {
    var up = document.querySelector('[data-upload]');
    if (up) up.addEventListener('change', function () {
      var l = document.querySelector('[data-upload-label]');
      if (up.files[0]) { l.innerHTML = '✓ ' + up.files[0].name.replace(/[<>&"]/g, '') + ' <small>Tap to change</small>'; up.closest('.o-upload').classList.add('has-file'); }
    });
  })();
</script>
@endpush
