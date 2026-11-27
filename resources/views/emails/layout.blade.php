{{-- Shared email shell: inline styles + tables only (works in Gmail, Outlook, Apple Mail). --}}
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light only">
  <meta name="supported-color-schemes" content="light">
  <title>@yield('title', 'SRM Fintech Solutions')</title>
</head>
<body style="margin:0;padding:0;background:#eef2f9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0d1424;-webkit-font-smoothing:antialiased">
  {{-- Inbox preview text --}}
  <div style="display:none;max-height:0;overflow:hidden;opacity:0">@yield('preheader')</div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#eef2f9" style="background:#eef2f9;padding:28px 12px">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px">

        {{-- Header --}}
        <tr><td bgcolor="#1f56e0" style="background:#1f56e0;background-image:linear-gradient(135deg,#2f6bff 0%,#1f56e0 45%,#0d2f8f 100%);border-radius:18px 18px 0 0;padding:26px 28px 24px;text-align:center">
          <a href="{{ url('/') }}" style="text-decoration:none"><img src="{{ asset('images/logo-dark.png') }}" alt="SRM Fintech Solutions" height="46" style="height:46px;border:0;display:inline-block"></a>
        </td></tr>

        {{-- Body --}}
        <tr><td bgcolor="#ffffff" style="background:#ffffff;padding:32px 30px 10px;border-left:1px solid #e3e8f2;border-right:1px solid #e3e8f2">
          @yield('content')
        </td></tr>

        {{-- Security strip --}}
        <tr><td bgcolor="#ffffff" style="background:#ffffff;padding:0 30px 26px;border-left:1px solid #e3e8f2;border-right:1px solid #e3e8f2">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f6f8fc" style="background:#f6f8fc;border-radius:12px">
            <tr><td style="padding:12px 14px;font-size:12.5px;line-height:1.55;color:#5b6577">
              🔒 <b style="color:#0d1424">Security reminder:</b> SRM Fintech Solutions will never ask for your password, verification code, UPI PIN or wallet recovery phrase.
            </td></tr>
          </table>
        </td></tr>

        {{-- Footer --}}
        <tr><td bgcolor="#0a0f2e" style="background:#0a0f2e;border-radius:0 0 18px 18px;padding:20px 28px;text-align:center;font-size:12px;line-height:1.7;color:#aab4c8">
          <b style="color:#ffffff">SRM Fintech Solutions</b><br>
          <a href="{{ url('/') }}" style="color:#7fa6ff;text-decoration:none">srmfintechsolutions.com</a> &nbsp;·&nbsp;
          <a href="mailto:{{ \App\Models\Setting::get('support_email', config('mail.from.address')) }}" style="color:#7fa6ff;text-decoration:none">{{ \App\Models\Setting::get('support_email', config('mail.from.address')) }}</a><br>
          <span style="color:#6f7a90">@yield('footnote', 'This is an automated message. Please do not reply to this email.')</span>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
