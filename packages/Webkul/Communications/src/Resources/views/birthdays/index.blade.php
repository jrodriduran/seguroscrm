<x-admin::layouts>
    <x-slot:title>@lang('communications::app.birthdays.title')</x-slot>

    <div class="flex flex-col gap-4" v-pre>
        <div class="tw-card p-4">
            <h1 class="text-xl font-bold dark:text-white">@lang('communications::app.birthdays.title')</h1>
            <p class="text-sm text-gray-500">@lang('communications::app.birthdays.description')</p>
        </div>

        <div class="tw-card tw-info p-4 text-sm">
            <p>@lang('communications::app.birthdays.preview-only')</p>
            <p>@lang('communications::app.birthdays.data-note')</p>
        </div>

        <form method="GET" action="{{ route('admin.communications.birthdays.index') }}" class="tw-card p-4 flex flex-wrap items-end gap-3">
            <label>
                <span class="block text-sm">@lang('communications::app.birthdays.date')</span>
                <input type="date" name="date" value="{{ $day->toDateString() }}" required class="border rounded p-2 dark:bg-gray-900 dark:border-gray-800">
            </label>
            <button type="submit" class="tw-btn tw-btn-primary">@lang('communications::app.birthdays.show')</button>
            <a href="{{ route('admin.communications.birthdays.index') }}" class="tw-btn">@lang('communications::app.birthdays.today')</a>
            <span class="text-sm text-gray-500">{{ $zone }} · @lang('communications::app.birthdays.total', ['count' => $contacts->total()])</span>
        </form>

        <div class="tw-card p-4" style="overflow-x: auto;">
            <table class="w-full text-sm" style="text-align: start;">
                <thead>
                    <tr>
                        <th class="p-3">@lang('communications::app.birthdays.contact')</th>
                        <th class="p-3">@lang('communications::app.birthdays.owner')</th>
                        <th class="p-3">WhatsApp</th>
                        <th class="p-3">@lang('communications::app.birthdays.check')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $contact)
                        <tr class="border-t dark:border-gray-800">
                            <td class="p-3">{{ $contact->name }}</td>
                            <td class="p-3">{{ $contact->owner_name ?: trans('communications::app.birthdays.unassigned') }}</td>
                            <td class="p-3">{{ $contact->phone_suffix ?: '—' }}</td>
                            <td class="p-3">{{ trans('communications::app.birthdays.'.$contact->review_status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-4">@lang('communications::app.birthdays.empty')</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $contacts->links() }}
        </div>
    </div>
</x-admin::layouts>
