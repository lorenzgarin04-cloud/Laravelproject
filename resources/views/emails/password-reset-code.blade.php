<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#ebfef1;font-family:Arial,Helvetica,sans-serif;color:#0f1f17;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ebfef1;padding:32px 12px;">
  <tr><td align="center">
    <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px;width:100%;background:#ffffff;border:1px solid #bccabe;border-radius:12px;overflow:hidden;">
      <tr><td style="background:#006a41;height:6px;font-size:0;line-height:0;">&nbsp;</td></tr>
      <tr><td style="padding:28px 32px 8px;">
        <p style="margin:0;font-size:13px;font-weight:bold;letter-spacing:1px;color:#006a41;">DE GUZMAN DRUGMART</p>
        <h1 style="margin:12px 0 8px;font-size:22px;color:#0f1f17;">Password reset code</h1>
        <p style="margin:0;font-size:15px;line-height:22px;color:#3d4a41;">Hi {{ $user->name }}, use the code below to reset your password.</p>
      </td></tr>
      <tr><td align="center" style="padding:20px 32px;">
        <div style="display:inline-block;background:#e5f8eb;border:1px solid #bccabe;border-radius:10px;padding:16px 28px;font-size:34px;font-weight:bold;letter-spacing:10px;color:#006a41;font-family:'Courier New',monospace;">{{ $code }}</div>
      </td></tr>
      <tr><td style="padding:0 32px 28px;">
        <p style="margin:0 0 10px;font-size:14px;line-height:21px;color:#3d4a41;">This code expires in <strong>{{ $minutes }} minutes</strong>. Never share it with anyone.</p>
        <p style="margin:0;font-size:13px;line-height:20px;color:#6d7a70;">If you did not request this, you can safely ignore this email. Your password will not change.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
