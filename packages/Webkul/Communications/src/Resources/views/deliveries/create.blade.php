<x-admin::layouts>
    <x-slot:title>
        @lang('communications::app.deliveries.new')
    </x-slot>

    <form method="POST" action="{{ route('admin.communications.deliveries.store') }}" class="flex flex-col gap-4" v-pre>
        @csrf

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="communications.deliveries" />
                <div class="text-xl font-bold dark:text-white">@lang('communications::app.deliveries.new'){{ $person ? ' · '.$person->name : '' }}</div>
            </div>

            <div class="flex gap-2">
                <a href="{{ $person ? route('admin.contacts.persons.view', $person->id) : route('admin.communications.deliveries.index') }}" class="tw-btn">@lang('communications::app.templates.back')</a>
                <button type="submit" class="tw-btn tw-btn-primary">@lang('communications::app.deliveries.request')</button>
            </div>
        </div>

        @if ($errors->any())
            <div class="tw-card tw-overdue p-3 text-sm">{{ $errors->first() }}</div>
        @endif

        <section class="tw-card tw-info grid gap-3 p-4 md:grid-cols-2">
            @if ($person)
                <input type="hidden" name="person_id" value="{{ $person->id }}">
                <p class="text-sm md:col-span-2 dark:text-gray-300">
                    {{ $address ? '📍 '.trim(implode(', ', array_filter([$address['address'] ?? null, $address['city'] ?? null, trim(($address['state'] ?? '').' '.($address['postcode'] ?? ''))]))) : '⚠ '.trans('communications::app.sequences.no-address') }}
                </p>
            @else
                <div class="md:col-span-2">
                    <label class="tw-label">@lang('communications::app.deliveries.client-id')</label>
                    <input type="number" name="person_id" required class="tw-input" value="{{ old('person_id') }}">
                    <span class="tw-meta">@lang('communications::app.deliveries.client-id-info')</span>
                </div>
            @endif

            <div>
                <label class="tw-label">@lang('communications::app.deliveries.item')</label>
                <select name="item" class="tw-input">
                    @foreach (\Webkul\Communications\Models\Delivery::ITEMS as $item)
                        <option value="{{ $item }}" @selected(old('item') === $item)>@lang('communications::app.deliveries.items.'.$item)</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="tw-label">@lang('communications::app.deliveries.cost')</label>
                <input type="number" name="cost" min="0" step="0.01" class="tw-input" value="{{ old('cost') }}">
                <span class="tw-meta">@lang('communications::app.deliveries.cost-info', ['limit' => number_format($limit, 2)])</span>
            </div>

            <div class="md:col-span-2">
                <label class="tw-label">@lang('communications::app.deliveries.note')</label>
                <input type="text" name="note" maxlength="500" class="tw-input" value="{{ old('note') }}" placeholder="@lang('communications::app.deliveries.note-placeholder')">
            </div>

            <div>
                <label class="tw-label">@lang('communications::app.deliveries.assignee')</label>
                <select name="assigned_to" class="tw-input">
                    @foreach ($users as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('assigned_to', $defaultAssignee) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </section>
    </form>
</x-admin::layouts>
