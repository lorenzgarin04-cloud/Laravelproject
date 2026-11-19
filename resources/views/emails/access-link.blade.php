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
        @if ($user->isAdmin())
            <h1 style="margin:12px 0 8px;font-size:22px;color:#0f1f17;">Set up your admin account</h1>
            <p style="margin:0;font-size:15px;line-height:22px;color:#3d4a41;">Hi {{ $user->name }}, an administrator created an admin account for you. Click the button below to choose your password.</p>
        @else
            <h1 style="margin:12px 0 8px;font-size:22px;color:#0f1f17;">Your cashier account is ready</h1>
            <p style="margin:0;font-size:15px;line-height:22px;color:#3d4a41;">Hi {{ $user->name }}, an administrator created a cashier account for you. Click the button below to open your dashboard. No password needed.</p>
        @endif
      </td></tr>
      <tr><td align="center" style="padding:22px 32px;">
        <a href="{{ $url }}" style="display:inline-block;background:#00945c;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:14px 28px;border-radius:10px;">{{ $user->isAdmin() ? 'Set my password' : 'Open my dashboard' }}</a>
      </td></tr>
      <tr><td style="padding:0 32px 28px;">
        <p style="margin:0 0 10px;font-size:14px;line-height:21px;color:#3d4a41;">This link works <strong>once</strong> and expires in <strong>{{ $hours }} hours</strong>. Please do not forward it.</p>
        <p style="margin:0 0 10px;font-size:12px;line-height:18px;color:#6d7a70;">If the button does not work, copy this address into your browser:<br><span style="word-break:break-all;color:#006a41;">{{ $url }}</span></p>
        <p style="margin:0;font-size:12px;line-height:18px;color:#6d7a70;">Did not expect this email? You can ignore it and nothing will happen.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
