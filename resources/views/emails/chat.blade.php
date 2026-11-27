<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Chat</title></head>
<body style="margin:0;padding:0;background:#f3f5f9;font-family:Arial,Helvetica,sans-serif;color:#0d1424">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f9;padding:32px 12px">
  <tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden">
      <tr><td style="background:#0a0f2e;padding:22px 28px;text-align:center">
        <img src="{{ asset('images/logo-dark.png') }}" alt="SRM Fintech Solutions" height="44" style="height:44px;border:0">
      </td></tr>
      <tr><td style="padding:28px 28px 8px">
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
          <p style="margin:0 0 20px;font-size:13px;color:#667085">You can also simply reply to this email. We never ask for your password, OTP, UPI PIN or wallet seed phrase.</p>
        @endif
      </td></tr>
      <tr><td style="padding:16px 28px 26px;border-top:1px solid #eef0f4;font-size:12px;color:#98a2b3;text-align:center">SRM Fintech Solutions · {{ config('mail.from.address') }}</td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
