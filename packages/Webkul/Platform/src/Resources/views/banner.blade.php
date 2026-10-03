{{-- Subscription and support banners at the top of every admin page. --}}
@php
    $platformState = app(\Webkul\Platform\Services\PlatformState::class);
    $platformStatus = $platformState->status();
@endphp

@if (session('platform_support'))
    <div style="margin: 0 0 12px; padding: 8px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; color: #1e3a8a; background: #dbeafe; border: 1px solid #bfdbfe;">
        🛟 @lang('platform::app.support.banner')
    </div>
@endif

@if (in_array($platformStatus, ['warning', 'read_only'], true))
    <div style="margin: 0 0 12px; padding: 10px 14px; border-radius: 10px; font-size: 13px; color: {{ $platformStatus === 'read_only' ? '#9f1239' : '#92400e' }}; background: {{ $platformStatus === 'read_only' ? '#ffe4e6' : '#fef3c7' }}; border: 1px solid {{ $platformStatus === 'read_only' ? '#fecdd3' : '#fde68a' }};">
        <strong>{{ $platformStatus === 'read_only' ? '🔒 '.trans('platform::app.read-only.title') : '⚠ '.trans('platform::app.warning.title') }}</strong>
        — {{ $platformState->message() ?: trans('platform::app.'.($platformStatus === 'read_only' ? 'read-only' : 'warning').'.default') }}
    </div>
@endif
