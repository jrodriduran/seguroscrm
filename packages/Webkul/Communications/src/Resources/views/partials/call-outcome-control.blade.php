{{-- Call outcome picker, used inside activity forms. Shown for calls only. --}}
@php
    $cmOutcomes = \Webkul\Communications\Models\CallOutcome::forAgency(null, true);
    $cmCurrent = $current ?? null;
@endphp

<x-admin::form.control-group v-if="{{ $condition ?? 'true' }}">
    <x-admin::form.control-group.label>
        @lang('communications::app.outcomes.label')
    </x-admin::form.control-group.label>

    <x-admin::form.control-group.control
        type="select"
        name="outcome"
        :value="$cmCurrent"
        :label="trans('communications::app.outcomes.label')"
    >
        <option value="">@lang('communications::app.outcomes.none')</option>

        @foreach ($cmOutcomes as $outcome)
            <option value="{{ $outcome->code }}">{{ $outcome->label }}</option>
        @endforeach

        @if ($cmCurrent && ! $cmOutcomes->contains('code', $cmCurrent))
            <option value="{{ $cmCurrent }}">{{ \Webkul\Communications\Models\CallOutcome::labelFor($cmCurrent) }}</option>
        @endif
    </x-admin::form.control-group.control>
</x-admin::form.control-group>
