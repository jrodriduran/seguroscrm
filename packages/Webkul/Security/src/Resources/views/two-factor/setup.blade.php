<x-admin::layouts>
    <x-slot:title>
        @lang('security::app.two-factor.setup.title')
    </x-slot>

    @php
        $inputClass = 'w-full rounded-md border px-3 py-2.5 text-sm text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-white';
    @endphp

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="account.security" />

                <div class="text-xl font-bold dark:text-white">
                    @lang('security::app.two-factor.setup.title')
                </div>
            </div>
        </div>

        <div class="box-shadow rounded-lg border bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                        @lang('security::app.two-factor.setup.heading')
                    </p>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        @lang('security::app.two-factor.setup.info')
                    </p>
                </div>

                @if ($enabled)
                    <span class="rounded-md px-2.5 py-1 text-xs font-semibold" style="color: #059669; background: rgba(5, 150, 105, 0.1);">
                        @lang('security::app.two-factor.setup.status-on')
                    </span>
                @else
                    <span class="rounded-md px-2.5 py-1 text-xs font-semibold" style="color: #64748b; background: rgba(100, 116, 139, 0.1);">
                        @lang('security::app.two-factor.setup.status-off')
                    </span>
                @endif
            </div>

            @if ($required && ! $enabled)
                <div class="mt-4 rounded-md border px-4 py-3 text-sm" style="border-color: rgba(217, 119, 6, 0.35); background: rgba(217, 119, 6, 0.08); color: #92400e;">
                    @lang('security::app.two-factor.setup.required-notice')
                </div>
            @endif

            @error('code')
                <div class="mt-4 rounded-md border px-4 py-3 text-sm" style="border-color: rgba(220, 38, 38, 0.3); background: rgba(220, 38, 38, 0.06); color: #b91c1c;">
                    {{ $message }}
                </div>
            @enderror

            {{-- Freshly generated recovery codes: shown once --}}
            @if ($recoveryCodes)
                <div class="mt-5 rounded-lg border p-4" style="border-color: rgba(79, 124, 255, 0.3); background: rgba(79, 124, 255, 0.05);">
                    <p class="text-sm font-semibold text-gray-800 dark:text-white">
                        @lang('security::app.two-factor.setup.recovery-title')
                    </p>

                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                        @lang('security::app.two-factor.setup.recovery-info')
                    </p>

                    <div id="nx-recovery-codes" class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm text-gray-800 dark:text-white max-sm:grid-cols-1">
                        @foreach ($recoveryCodes as $code)
                            <span class="rounded-md border bg-white px-3 py-1.5 text-center dark:border-gray-800 dark:bg-gray-900">{{ $code }}</span>
                        @endforeach
                    </div>

                    <button type="button" class="secondary-button mt-3" data-nx-copy="#nx-recovery-codes">
                        @lang('security::app.two-factor.setup.copy')
                    </button>
                </div>
            @endif

            @if (! $enabled)
                {{-- Enrolment --}}
                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="flex flex-col gap-4 text-sm text-gray-700 dark:text-gray-300">
                        <p><span class="font-semibold">1.</span> @lang('security::app.two-factor.setup.step-app')</p>

                        <p><span class="font-semibold">2.</span> @lang('security::app.two-factor.setup.step-scan')</p>

                        <div class="flex flex-wrap items-center gap-5">
                            <div
                                id="nx-qr"
                                data-uri="{{ $otpauthUri }}"
                                class="flex items-center justify-center rounded-lg border bg-white p-2"
                                style="width: 180px; height: 180px;"
                            ></div>

                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">@lang('security::app.two-factor.setup.manual-key')</p>

                                <p class="mt-1 select-all font-mono text-sm font-semibold text-gray-800 dark:text-white">{{ $secret }}</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.security.two_factor.confirm') }}" class="flex flex-col gap-3 text-sm text-gray-700 dark:text-gray-300">
                        @csrf

                        <p><span class="font-semibold">3.</span> @lang('security::app.two-factor.setup.step-confirm')</p>

                        <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required maxlength="20" placeholder="123 456" class="{{ $inputClass }} max-w-[220px] text-center font-semibold" style="letter-spacing: 0.25em;">

                        <div>
                            <button type="submit" class="primary-button">
                                @lang('security::app.two-factor.setup.enable')
                            </button>
                        </div>
                    </form>
                </div>
            @else
                {{-- Management --}}
                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <form method="POST" action="{{ route('admin.security.two_factor.recovery_codes') }}" class="flex flex-col gap-2 rounded-lg border p-4 dark:border-gray-800">
                        @csrf

                        <p class="text-sm font-semibold text-gray-800 dark:text-white">@lang('security::app.two-factor.setup.recovery-codes')</p>

                        <p class="text-xs text-gray-600 dark:text-gray-300">
                            @lang('security::app.two-factor.setup.remaining', ['count' => $remainingCodes])
                        </p>

                        <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required maxlength="20" placeholder="@lang('security::app.two-factor.setup.current-code')" class="{{ $inputClass }} max-w-[220px]">

                        <div>
                            <button type="submit" class="secondary-button">@lang('security::app.two-factor.setup.regenerate')</button>
                        </div>
                    </form>

                    @unless ($required)
                        <form method="POST" action="{{ route('admin.security.two_factor.disable') }}" class="flex flex-col gap-2 rounded-lg border p-4 dark:border-gray-800">
                            @csrf

                            <p class="text-sm font-semibold text-gray-800 dark:text-white">@lang('security::app.two-factor.setup.disable-title')</p>

                            <p class="text-xs text-gray-600 dark:text-gray-300">@lang('security::app.two-factor.setup.disable-info')</p>

                            <input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required maxlength="20" placeholder="@lang('security::app.two-factor.setup.current-code')" class="{{ $inputClass }} max-w-[220px]">

                            <div>
                                <button type="submit" class="secondary-button" style="color: #dc2626; border-color: rgba(220, 38, 38, 0.4);">@lang('security::app.two-factor.setup.disable')</button>
                            </div>
                        </form>
                    @endunless
                </div>

                {{-- Trusted browsers --}}
                <div class="mt-6">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-800 dark:text-white">@lang('security::app.two-factor.setup.devices')</p>

                        @if ($devices->isNotEmpty())
                            <form method="POST" action="{{ route('admin.security.two_factor.forget_devices') }}">
                                @csrf

                                <button type="submit" class="transparent-button text-xs">@lang('security::app.two-factor.setup.forget-devices')</button>
                            </form>
                        @endif
                    </div>

                    @forelse ($devices as $device)
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 rounded-md border px-3 py-2 text-xs text-gray-600 dark:border-gray-800 dark:text-gray-300">
                            <span class="truncate" title="{{ $device->user_agent }}">{{ \Illuminate\Support\Str::limit($device->user_agent, 70) }}</span>
                            <span>{{ $device->ip_address }} · @lang('security::app.two-factor.setup.last-used', ['date' => core()->formatDate($device->last_used_at, 'd M Y H:i')])</span>
                        </div>
                    @empty
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">@lang('security::app.two-factor.setup.no-devices')</p>
                    @endforelse
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        {{-- QR drawn in the browser: the secret never leaves this page --}}
        <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>

        <script>
            // Vue mounts #app on "load" and replaces its DOM, so draw once that has happened.
            window.addEventListener('load', function () {
                setTimeout(function () {
                    var box = document.getElementById('nx-qr');

                    if (box && window.qrcode) {
                        var qr = window.qrcode(0, 'M');

                        qr.addData(box.dataset.uri);
                        qr.make();

                        box.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
                    }
                }, 0);
            });

            document.addEventListener('click', function (event) {
                var button = event.target.closest && event.target.closest('[data-nx-copy]');
                var source = button && document.querySelector(button.dataset.nxCopy);

                if (source && navigator.clipboard) {
                    navigator.clipboard.writeText(source.innerText.trim());
                }
            });
        </script>
    @endpush
</x-admin::layouts>
