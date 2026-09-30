{{-- Human description of a trigger, e.g. "Enters ACA › Consent & docs". --}}
@switch($trigger->event)
    @case('stage_entered')
        @lang('communications::app.triggers.labels.stage_entered', ['stage' => $stages[$trigger->option('stage_id')] ?? '?'])
        @break
    @case('stage_idle')
        @lang('communications::app.triggers.labels.stage_idle', ['stage' => $stages[$trigger->option('stage_id')] ?? '?', 'days' => $trigger->option('days')])
        @break
    @case('policy_status')
        @lang('communications::app.triggers.labels.policy_status', ['status' => trans()->has('communications::app.triggers.statuses.'.$trigger->option('status')) ? trans('communications::app.triggers.statuses.'.$trigger->option('status')) : $trigger->option('status')])
        @break
    @case('call_outcome')
        @lang('communications::app.triggers.labels.call_outcome', ['outcome' => \Webkul\Communications\Models\CallOutcome::labelFor($trigger->option('outcome'))])
        @break
    @case('renewal_before')
        @lang('communications::app.triggers.labels.renewal_before', ['days' => $trigger->option('days')])
        @break
    @default
        @lang('communications::app.triggers.labels.'.$trigger->event)
@endswitch
