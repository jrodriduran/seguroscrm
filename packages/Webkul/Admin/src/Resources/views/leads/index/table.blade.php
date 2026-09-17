{!! view_render_event('admin.leads.index.table.before') !!}

<!-- Insurance Book of Business Quick Filters -->
<div class="flex items-center gap-2 mb-3 flex-wrap">
    <a
        href="{{ route('admin.leads.index', ['view_type' => 'table', 'pipeline_id' => request('pipeline_id')]) }}"
        class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition {{ ! request()->input('type.in') ? 'bg-brandColor text-white shadow-sm' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-900 dark:border-gray-800 dark:text-gray-300' }}"
    >
        @lang('admin::insurance.filters.all_leads')
    </a>

    <a
        href="{{ route('admin.leads.index', array_merge(request()->query(), ['view_type' => 'table', 'type' => ['in' => 'won']])) }}"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition {{ request()->input('type.in') === 'won' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-100 dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-300' }}"
    >
        <span class="w-2 h-2 rounded-full {{ request()->input('type.in') === 'won' ? 'bg-white' : 'bg-emerald-500' }}"></span>
        @lang('admin::insurance.filters.book_of_business')
    </a>
</div>

<x-admin::datagrid :src="route('admin.leads.index')">
    <!-- DataGrid Shimmer -->
    <x-admin::shimmer.datagrid />

    <x-slot:toolbar-right-after>
        @include('admin::leads.index.view-switcher')

        <x-admin::datagrid.column-settings />
    </x-slot>
</x-admin::datagrid>

{!! view_render_event('admin.leads.index.table.after') !!}