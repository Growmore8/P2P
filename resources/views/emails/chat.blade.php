@extends('emails.layout')
@section('title', 'Chat message')
@section('footnote', 'You can reply directly to this email.')

@section('content')
        @if ($type === 'reply')
          <p style="margin:0 0 12px;font-size:16px">Hi {{ $c->displayName() }},</p>
          <p style="margin:0 0 16px;font-size:15px;color:#475467">Our support team replied to your chat on srmfintechsolutions.com:</p>
        @else
          <p style="margin:0 0 16px;font-size:15px;color:#475467"><b>{{ $c->displayName() }}</b>{{ $c->contactEmail() ? ' (' . $c->contactEmail() . ')' : '' }} sent a chat message{{ $c->page ? ' from ' . $c->page : '' }}:</p>
        @endif
        <div style="border-left:4px solid #1f56e0;background:#fbfcff;padding:12px 16px;margin:0 0 20px;font-size:14.5px;line-height:1.6;white-space:pre-line">{{ $msg->body }}</div>
        <p style="text-align:center;margin:22px 0">
          <a href="{{ $url }}" style="display:inline-block;background:#1f56e0;color:#ffffff;text-decoration:none;font-weight:bold;padding:13px 26px;border-radius:10px">{{ $type === 'reply' ? 'Continue the chat' : 'Reply in admin' }}</a>
        </p>
        @if ($type === 'reply')
          <p style="margin:0 0 20px;font-size:13px;color:#667085">You can also simply reply to this email.</p>
        @endif
@endsection
