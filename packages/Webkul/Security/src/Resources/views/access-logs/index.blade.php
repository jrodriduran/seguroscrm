<x-admin::layouts>
    <x-slot:title>
        @lang('security::app.access-logs.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="settings.security.access_logs" />

                <div class="text-xl font-bold dark:text-white">
                    @lang('security::app.access-logs.title')
                </div>
            </div>
        </div>

        <x-admin::datagrid :src="route('admin.settings.security.access_logs.index')">
            <x-admin::shimmer.datagrid />
        </x-admin::datagrid>
    </div>
</x-admin::layouts>
