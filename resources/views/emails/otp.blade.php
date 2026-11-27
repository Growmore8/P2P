@extends('emails.layout')
@section('title', 'Your verification code')
@section('preheader', $code . ' is your code. It expires in ' . $minutes . ' minutes.')

@section('content')
  <p style="margin:0 0 6px;font-size:13px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#1f56e0">{{ $reset ? 'Password reset' : 'Email verification' }}</p>
  <h1 style="margin:0 0 14px;font-size:24px;line-height:1.25;font-weight:800;color:#0d1424">{{ $reset ? 'Reset your password' : 'Verify your email' }}</h1>
  <p style="margin:0 0 6px;font-size:15.5px;color:#0d1424">Hi {{ $name ?: 'there' }},</p>
  <p style="margin:0 0 22px;font-size:15px;line-height:1.6;color:#5b6577">
    {{ $reset ? 'Enter this code to set a new password for your SRM Fintech Solutions account.' : 'Enter this code to finish creating your SRM Fintech Solutions account.' }}
  </p>

  {{-- Code --}}
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 14px">
    <tr><td align="center" bgcolor="#eef3ff" style="background:#eef3ff;border:2px dashed #9db8ff;border-radius:14px;padding:20px 10px">
      <div style="font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#5b6577;margin-bottom:8px">Your code</div>
      <div style="font-family:'Courier New',Consolas,monospace;font-size:38px;line-height:1;font-weight:800;letter-spacing:12px;color:#1f56e0;padding-left:12px">{{ $code }}</div>
    </td></tr>
  </table>

  <p style="margin:0 0 22px;text-align:center">
    <span style="display:inline-block;background:#fff4dd;color:#8a5a00;font-size:13px;font-weight:700;border-radius:99px;padding:6px 14px">⏱ Expires in {{ $minutes }} minutes</span>
  </p>

  <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#5b6577">
    If you did not request this code, please ignore this email. Your account remains secure. <b style="color:#0d1424">For your security, do not share this code with anyone.</b>
  </p>
@endsection
