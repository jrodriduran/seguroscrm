<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('platform::app.suspended.title') · {{ config('app.name') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; background: #f3f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1e293b; }
        .box { max-width: 460px; width: 100%; background: #fff; border: 1px solid #e5e9f2; border-radius: 16px; padding: 32px; text-align: center; box-shadow: 0 10px 30px -18px rgba(15, 23, 42, .25); }
        h1 { font-size: 21px; margin: 12px 0 8px; }
        p { color: #64748b; line-height: 1.55; }
        .icon { font-size: 40px; }
        form button { margin-top: 16px; border: 1px solid #e2e8f0; background: #fff; border-radius: 10px; padding: 9px 16px; font-weight: 600; cursor: pointer; }
        @media (prefers-color-scheme: dark) { body { background: #0b1220; color: #e2e8f0; } .box { background: #111a2f; border-color: #1f2a44; } form button { background: #111a2f; color: #e2e8f0; border-color: #1f2a44; } }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">⏸</div>
        <h1>@lang('platform::app.suspended.title')</h1>
        <p>{{ $message ?: trans('platform::app.suspended.default') }}</p>
        <p>@lang('platform::app.suspended.data-safe')</p>

        @if (Route::has('admin.session.destroy') && auth()->guard('user')->check())
            <form method="POST" action="{{ route('admin.session.destroy') }}">
                @csrf
                @method('DELETE')
                <button type="submit">@lang('platform::app.suspended.logout')</button>
            </form>
        @endif
    </div>
</body>
</html>
