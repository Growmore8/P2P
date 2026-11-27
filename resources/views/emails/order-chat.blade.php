@extends('emails.layout')
@section('title', 'Order ' . $o->order_no)
@section('preheader', 'New message on order ' . $o->order_no)
@section('footnote', 'Please reply in the order chat on our website.')

@section('content')
  <p style="margin:0 0 6px;font-size:13px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#1f56e0">Order {{ $o->order_no }}</p>
  @if ($toClient)
    <h1 style="margin:0 0 14px;font-size:22px;line-height:1.3;font-weight:800;color:#0d1424">You have a new message</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#5b6577">Hi {{ $o->user->name }}, SRM Fintech Solutions sent you a message about your {{ $o->isBuy() ? 'buy' : 'sell' }} order of {{ number_format((float) $o->usdt_amount, 2) }} USDT.</p>
  @else
    <h1 style="margin:0 0 14px;font-size:22px;line-height:1.3;font-weight:800;color:#0d1424">Client message</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#5b6577"><b>{{ $o->user->name }}</b> ({{ $o->user->client_id }}) wrote on their {{ $o->isBuy() ? 'buy' : 'sell' }} order · status: {{ $o->statusLabel() }}.</p>
  @endif

  @if ($msg->body)
    <div style="border-left:4px solid #1f56e0;background:#f6f8fc;border-radius:0 10px 10px 0;padding:12px 16px;margin:0 0 16px;font-size:14.5px;line-height:1.6;color:#0d1424;white-space:pre-line">{{ $msg->body }}</div>
  @endif
  @if ($msg->attachment)
    <p style="margin:0 0 16px;font-size:13.5px;color:#5b6577">📎 A file is attached in the chat.</p>
  @endif

  <p style="text-align:center;margin:22px 0 26px">
    <a href="{{ $url }}" style="display:inline-block;background:#1f56e0;color:#ffffff;text-decoration:none;font-weight:bold;padding:13px 26px;border-radius:10px">{{ $toClient ? 'Open order chat' : 'Open in admin' }}</a>
  </p>
@endsection
