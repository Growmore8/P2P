@extends('emails.layout')
@section('title', $t->ticket_no)
@section('footnote', 'You can reply directly to this email to update your ticket.')

@section('content')
        @if ($type === 'created')
          <p style="margin:0 0 12px;font-size:16px">Hi {{ $t->user->name }},</p>
          <p style="margin:0 0 16px;font-size:15px;line-height:1.55;color:#475467">Thank you for contacting SRM Fintech Solutions. We have received your support ticket. Our team will review your case and get back to you as soon as possible.</p>
        @elseif ($type === 'reply')
          <p style="margin:0 0 12px;font-size:16px">Hi {{ $t->user->name }},</p>
          <p style="margin:0 0 16px;font-size:15px;line-height:1.55;color:#475467">Our support team has replied to your ticket:</p>
        @elseif ($type === 'staff_new')
          <p style="margin:0 0 16px;font-size:15px;color:#475467"><b>{{ $t->user->name }}</b> ({{ $t->user->email }}) opened a new ticket.</p>
        @else
          <p style="margin:0 0 16px;font-size:15px;color:#475467"><b>{{ $t->user->name }}</b> replied on their ticket.</p>
        @endif

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8fc;border-radius:12px;margin:0 0 18px">
          <tr><td style="padding:14px 16px;font-size:14px;line-height:1.7">
            <b>Ticket ID:</b> <span style="font-family:monospace;color:#1f56e0">{{ $t->ticket_no }}</span><br>
            <b>Category:</b> {{ $t->categoryLabel() }}<br>
            <b>Subject:</b> {{ $t->subject }}<br>
            @if ($t->order)<b>Order:</b> {{ $t->order->order_no }}<br>@endif
            <b>Status:</b> {{ $t->statusLabel() }}
          </td></tr>
        </table>

        @if ($msg)
          <div style="border-left:4px solid #1f56e0;background:#fbfcff;padding:12px 16px;margin:0 0 18px;font-size:14.5px;line-height:1.6;color:#0d1424;white-space:pre-line">{{ $msg->body }}</div>
        @endif

        <p style="text-align:center;margin:22px 0">
          <a href="{{ $url }}" style="display:inline-block;background:#1f56e0;color:#ffffff;text-decoration:none;font-weight:bold;padding:13px 26px;border-radius:10px">{{ str_starts_with($type, 'staff') ? 'Open in admin' : 'View your ticket' }}</a>
        </p>
        @unless (str_starts_with($type, 'staff'))
          <p style="margin:0 0 20px;font-size:13px;color:#667085">Please keep your ticket ID for reference.</p>
        @endunless
@endsection
