<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $agency['agency_name'] }}</title>
</head>
<body style="margin:0; padding:0; background:#f3f5f9; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f9; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background:#ffffff; border-radius:14px; overflow:hidden; border:1px solid #e5e9f2;">
                    <tr>
                        <td style="padding:18px 28px; background:linear-gradient(135deg,#4f6bff,#6d5dfc); color:#ffffff; font-size:17px; font-weight:700;">
                            {{ $agency['agency_name'] }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px; font-size:15px; line-height:1.6;">
                            {!! $body !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px; background:#f8fafc; border-top:1px solid #eef1f6; font-size:12px; line-height:1.6; color:#64748b;">
                            @if ($agency['agent_name'])
                                {{ $agency['agent_name'] }}@if ($agency['agent_email']) · <a href="mailto:{{ $agency['agent_email'] }}" style="color:#4f6bff;">{{ $agency['agent_email'] }}</a>@endif<br>
                            @endif
                            {{ $agency['agency_name'] }}@if ($agency['agency_phone']) · {{ $agency['agency_phone'] }}@endif @if ($agency['agency_whatsapp']) · WhatsApp {{ $agency['agency_whatsapp'] }}@endif
                            @if ($address)
                                <br>{{ $address }}
                            @endif
                            @if ($unsubscribe)
                                <br><br><a href="{{ $unsubscribe }}" style="color:#64748b;">@lang('communications::app.emails.unsubscribe', [], $locale)</a>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
