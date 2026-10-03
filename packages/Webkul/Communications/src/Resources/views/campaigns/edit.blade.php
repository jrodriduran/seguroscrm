<x-admin::layouts>
    <x-slot:title>
        {{ $campaign->exists ? $campaign->name : trans('communications::app.campaigns.new') }}
    </x-slot>

    @php
        $cmLocal = $campaign->scheduled_at ? $campaign->scheduled_at->copy()->setTimezone($timezone)->format('Y-m-d\TH:i') : '';
    @endphp

    <form method="POST" action="{{ $campaign->exists ? route('admin.communications.campaigns.update', $campaign->id) : route('admin.communications.campaigns.store') }}" class="flex flex-col gap-4" v-pre>
        @csrf
        @if ($campaign->exists)
            @method('PUT')
        @endif

        <input type="hidden" name="occasion" value="{{ old('occasion', $campaign->occasion) }}">

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="communications.campaigns" />
                <div class="text-xl font-bold dark:text-white">{{ $campaign->exists ? $campaign->name : trans('communications::app.campaigns.new') }}</div>
            </div>

            <div class="flex gap-2">
                <a href="{{ $campaign->exists ? route('admin.communications.campaigns.show', $campaign->id) : route('admin.communications.campaigns.index') }}" class="tw-btn">@lang('communications::app.templates.back')</a>
                <button type="submit" class="tw-btn tw-btn-primary">@lang('communications::app.campaigns.save')</button>
            </div>
        </div>

        @if ($errors->any())
            <div class="tw-card tw-overdue p-3 text-sm">{{ $errors->first() }}</div>
        @endif

        <section class="tw-card tw-info grid gap-3 p-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="tw-label">@lang('communications::app.campaigns.name')</label>
                <input type="text" name="name" required maxlength="150" class="tw-input" value="{{ old('name', $campaign->name) }}">
            </div>

            <div>
                <label class="tw-label">@lang('communications::app.campaigns.audience')</label>
                <select name="audience_id" required class="tw-input">
                    <option value="">—</option>
                    @foreach ($audiences as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('audience_id', $campaign->audience_id) === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('admin.communications.audiences.create') }}" class="tw-meta">+ @lang('communications::app.audiences.new')</a>
            </div>

            <div>
                <label class="tw-label">@lang('communications::app.campaigns.template')</label>
                <select name="template_id" required class="tw-input">
                    <option value="">—</option>
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}" @selected((string) old('template_id', $campaign->template_id) === (string) $template->id)>{{ $template->name }} ({{ trans('communications::app.templates.purposes.'.$template->purpose) }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="tw-label">@lang('communications::app.campaigns.channel')</label>
                <select name="channel" class="tw-input">
                    @foreach (\Webkul\Communications\Models\Campaign::CHANNELS as $channel)
                        <option value="{{ $channel }}" @selected(old('channel', $campaign->channel) === $channel)>@lang('communications::app.campaigns.channels.'.$channel)</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="tw-label">@lang('communications::app.campaigns.when', ['zone' => $timezone])</label>
                <input type="datetime-local" name="scheduled_at" class="tw-input" value="{{ old('scheduled_at', $cmLocal) }}">
                <span class="tw-meta">@lang('communications::app.campaigns.when-info')</span>
            </div>

            <p class="tw-meta md:col-span-2" style="white-space: normal;">@lang('communications::app.campaigns.form-info')</p>
        </section>
    </form>
</x-admin::layouts>
