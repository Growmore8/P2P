<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Your code</title></head>
<body style="margin:0;padding:0;background:#f3f5f9;font-family:Arial,Helvetica,sans-serif;color:#0d1424">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f9;padding:32px 12px">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:16px;overflow:hidden">
        <tr><td style="background:#0a0f2e;padding:22px 28px;text-align:center">
          <img src="{{ asset('images/logo-dark.png') }}" alt="SRM Fintech Solutions" height="44" style="height:44px;border:0">
        </td></tr>
        <tr><td style="padding:30px 28px 10px">
          <p style="margin:0 0 12px;font-size:16px">Hi {{ $name ?: 'there' }},</p>
          <p style="margin:0 0 20px;font-size:15px;line-height:1.5;color:#475467">
            {{ $reset ? 'Use this code to reset your SRM Fintech Solutions password:' : 'Use this code to verify your email address for SRM Fintech Solutions:' }}
          </p>
          <div style="font-size:34px;font-weight:bold;letter-spacing:10px;text-align:center;background:#eef3ff;color:#1f56e0;border-radius:12px;padding:18px 0;margin:0 0 20px">{{ $code }}</div>
          <p style="margin:0 0 8px;font-size:14px;color:#475467">This code expires in {{ $minutes }} minutes.</p>
          <p style="margin:0 0 24px;font-size:14px;color:#475467">If you didn't request it, you can ignore this email — your account is safe. <b>Never share this code with anyone</b>, including our staff.</p>
        </td></tr>
        <tr><td style="padding:16px 28px 26px;border-top:1px solid #eef0f4;font-size:12px;color:#98a2b3;text-align:center">
          SRM Fintech Solutions · {{ config('mail.from.address') }}
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
