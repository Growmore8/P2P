@extends('layouts.admin')
@section('title', 'Ticket ' . $ticket->ticket_no)
@section('heading', 'Ticket ' . $ticket->ticket_no)

@section('content')
@php $u = $ticket->user; @endphp
<a href="{{ route('admin.tickets.index') }}" class="muted" style="font-size:14px">← Back to tickets</a>

<div class="split" style="margin-top:14px">
  <div>
    <div class="box">
      <div class="box-head">
        <div><span class="badge {{ $ticket->statusClass() }}">{{ $ticket->statusLabel() }}</span> <span class="badge info">{{ $ticket->categoryIcon() }} {{ $ticket->categoryLabel() }}</span></div>
        <span class="muted" style="font-size:13px">Opened {{ $ticket->created_at->format('d M Y, h:i A') }}</span>
      </div>
      <h3 style="font-size:19px">{{ $ticket->subject }}</h3>

      <div class="thread" style="margin-top:16px">
        @foreach ($ticket->messages as $m)
          <div class="bubble {{ $m->is_staff ? 'me' : 'staff' }}">
            <div class="bubble-head">{{ $m->is_staff ? 'Support · ' . ($m->author?->name ?? 'Admin') : $u->name }} · {{ $m->created_at->format('d M, h:i A') }}</div>
            <div class="bubble-body">{{ $m->body }}</div>
            @if ($m->attachment)<a class="bubble-file" href="{{ route('files.ticket', $m) }}" target="_blank" rel="noopener">📎 View attachment</a>@endif
          </div>
        @endforeach
      </div>
    </div>

    <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="box form">
      @csrf
      <h3>Reply to client</h3>
      <div>
        <select class="input" onchange="if(this.value){var t=this.form.message;t.value=(t.value?t.value+'\n\n':'')+this.value;this.selectedIndex=0;t.focus();}">
          <option value="">Insert a quick reply…</option>
          <option>Thank you for reaching out. We are checking your order with our payments team and will update you shortly.</option>
          <option>We have verified your payment and your order is being processed. You will receive it shortly.</option>
          <option>We could not find a payment with the details provided. Please share the UTR / transaction hash and a screenshot of the payment.</option>
          <option>Your refund has been initiated to the source account. It usually reflects within 1–3 working days.</option>
          <option>Your issue has been resolved. Please let us know if you need anything else.</option>
        </select>
      </div>
      <textarea class="input" name="message" rows="5" maxlength="5000" placeholder="Write your reply — it will be emailed to {{ $u->email }}" required></textarea>
      <div class="form-grid">
        <div><label>After sending, mark as</label>
          <select class="input" name="status">
            <option value="answered">Answered — waiting for client</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        <div><label>Attachment (optional)</label><input class="input" type="file" name="attachment" accept="image/*,.pdf"></div>
      </div>
      <button class="btn btn-primary">Send reply &amp; email client</button>
    </form>
  </div>

  <div>
    <div class="box">
      <div class="box-head"><h3>Client</h3><a href="{{ route('admin.users.show', $u) }}" class="btn btn-ghost btn-sm">Profile</a></div>
      <dl class="kv" style="grid-template-columns:90px 1fr">
        <dt>Name</dt><dd>{{ $u->name }}</dd>
        <dt>Email</dt><dd>{{ $u->email }}</dd>
        <dt>Phone</dt><dd>{{ $u->phone }} <a class="green" target="_blank" rel="noopener" href="https://wa.me/{{ $u->whatsappNumber() }}?text={{ rawurlencode('Regarding your SRM ticket ' . $ticket->ticket_no) }}">WhatsApp ↗</a></dd>
        <dt>Country</dt><dd>{{ $u->countryName() }}</dd>
        <dt>KYC</dt><dd><span class="badge {{ $u->kyc_status === 'approved' ? 'ok' : 'warn' }}">{{ $u->kyc_status }}</span></dd>
      </dl>
    </div>
    @if ($ticket->order)
      @php $o = $ticket->order; @endphp
      <div class="box">
        <div class="box-head"><h3>Related order</h3><a href="{{ route('admin.orders.show', $o) }}" class="btn btn-ghost btn-sm">Open order</a></div>
        <dl class="kv" style="grid-template-columns:90px 1fr">
          <dt>Order</dt><dd class="mono">{{ $o->order_no }}</dd>
          <dt>Type</dt><dd>{{ strtoupper($o->side) }} · {{ $o->network }}</dd>
          <dt>Amount</dt><dd>{{ number_format($o->usdt_amount, 2) }} USDT · {{ $o->money($o->clientFiat()) }}</dd>
          <dt>Status</dt><dd><span class="badge {{ $o->statusClass() }}">{{ $o->statusLabel() }}</span></dd>
          @if ($o->user_reference)<dt>Client ref.</dt><dd class="mono">{{ $o->user_reference }}</dd>@endif
        </dl>
      </div>
    @endif
    <div class="box">
      <h3 style="margin-bottom:12px">Change status</h3>
      <form method="POST" action="{{ route('admin.tickets.status', $ticket) }}" class="actions">
        @csrf
        @foreach (\App\Models\Ticket::STATUSES as $k => $l)
          <button name="status" value="{{ $k }}" class="btn btn-sm {{ $ticket->status === $k ? 'btn-primary' : 'btn-ghost' }}">{{ $l }}</button>
        @endforeach
      </form>
    </div>
    @if ($others->isNotEmpty())
      <div class="box">
        <h3 style="margin-bottom:12px">Other tickets from this client</h3>
        @foreach ($others as $x)
          <a href="{{ route('admin.tickets.show', $x) }}" class="order-item" style="margin-bottom:8px"><span><b>{{ \Illuminate\Support\Str::limit($x->subject, 36) }}</b><span class="sub muted mono" style="display:block;font-size:12px">{{ $x->ticket_no }}</span></span><span class="badge {{ $x->statusClass() }}">{{ $x->statusLabel() }}</span></a>
        @endforeach
      </div>
    @endif
  </div>
</div>
@endsection
