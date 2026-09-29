<x-admin::layouts.anonymous>
    <x-slot:title>
        @lang('security::app.two-factor.challenge.title')
    </x-slot>

    <div class="flex h-[100vh] flex-col items-center justify-center gap-10">
        <div class="flex flex-col items-center gap-5">
            @if ($logo = core()->getConfigData('general.general.admin_logo.logo_image'))
                <img class="h-10 w-[110px]" src="{{ Storage::url($logo) }}" alt="{{ config('app.name') }}" />
            @else
                <img class="w-max" src="{{ vite()->asset('images/logo.svg') }}" alt="{{ config('app.name') }}" />
            @endif

            <div class="box-shadow flex w-[360px] max-w-[92vw] flex-col rounded-md bg-white dark:bg-gray-900">
                <form method="POST" action="{{ route('admin.security.two_factor.challenge.verify') }}">
                    @csrf

                    <div class="p-4">
                        <p class="text-xl font-bold text-gray-800 dark:text-white">
                            @lang('security::app.two-factor.challenge.title')
                        </p>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            @lang('security::app.two-factor.challenge.info')
                        </p>
                    </div>

                    <div class="border-y p-4 dark:border-gray-800">
                        <label for="code" class="mb-1.5 block text-xs font-medium text-gray-800 dark:text-white">
                            @lang('security::app.two-factor.challenge.code')
                        </label>

                        <input
                            id="code"
                            name="code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            autofocus
                            required
                            maxlength="20"
                            placeholder="123 456"
                            class="w-full rounded-md border px-3 py-2.5 text-center text-lg font-semibold text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-white"
                            style="letter-spacing: 0.3em;"
                        >

                        @error('code')
                            <p class="mt-1.5 text-xs italic text-red-600">{{ $message }}</p>
                        @enderror

                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            @lang('security::app.two-factor.challenge.recovery-hint')
                        </p>

                        @if ($trustDays > 0)
                            <label class="mt-4 flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="trust_device" value="1" class="h-4 w-4">
                                @lang('security::app.two-factor.challenge.trust', ['days' => $trustDays])
                            </label>
                        @endif
                    </div>

                    <div class="flex items-center justify-between p-4">
                        <a
                            href="{{ route('admin.session.destroy') }}"
                            class="text-xs font-semibold text-brandColor"
                            onclick="event.preventDefault(); document.getElementById('two-factor-logout').submit();"
                        >
                            @lang('security::app.two-factor.challenge.sign-out')
                        </a>

                        <button type="submit" class="primary-button">
                            @lang('security::app.two-factor.challenge.verify')
                        </button>
                    </div>
                </form>

                <form id="two-factor-logout" method="POST" action="{{ route('admin.session.destroy') }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>
</x-admin::layouts.anonymous>
