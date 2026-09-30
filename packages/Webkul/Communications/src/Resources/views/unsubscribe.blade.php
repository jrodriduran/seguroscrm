<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('communications::app.emails.unsubscribe-title')</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f3f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1e293b; padding: 16px; }
        .box { max-width: 420px; width: 100%; background: #fff; border: 1px solid #e5e9f2; border-radius: 16px; padding: 28px; text-align: center; box-shadow: 0 10px 30px -18px rgba(15, 23, 42, .25); }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p { color: #64748b; line-height: 1.55; }
        button { margin-top: 12px; border: 0; border-radius: 10px; padding: 11px 18px; font-size: 14px; font-weight: 600; color: #fff; background: linear-gradient(135deg, #4f6bff, #6d5dfc); cursor: pointer; }
    </style>
</head>
<body>
    <div class="box">
        <h1>{{ $agency }}</h1>

        @if ($done)
            <p>@lang('communications::app.emails.unsubscribed')</p>
        @else
            <p>@lang('communications::app.emails.unsubscribe-question')</p>

            <form method="POST" action="{{ $action }}">
                <button type="submit">@lang('communications::app.emails.unsubscribe-confirm')</button>
            </form>
        @endif
    </div>
</body>
</html>
