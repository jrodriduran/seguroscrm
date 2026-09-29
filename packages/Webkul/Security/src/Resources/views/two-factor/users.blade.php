<x-admin::layouts>
    <x-slot:title>
        @lang('security::app.menu.two-factor')
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="settings.security.two_factor" />

                <div class="text-xl font-bold dark:text-white">
                    @lang('security::app.menu.two-factor')
                </div>
            </div>

            <a href="{{ route('admin.security.two_factor.setup') }}" class="secondary-button">
                @lang('security::app.two-factor.users.my-account')
            </a>
        </div>

        <x-admin::datagrid :src="route('admin.settings.security.two_factor.index')">
            <x-admin::shimmer.datagrid />
        </x-admin::datagrid>
    </div>
</x-admin::layouts>
