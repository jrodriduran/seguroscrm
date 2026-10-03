{{-- Readable condition, e.g. "Sexo: Female" or "Edad mínima: 30". --}}
@php
    $value = $rule['value'] ?? '';
    $display = match ($rule['field'] ?? '') {
        'birthday_month', 'renewal_month' => $value === 'current' ? trans('communications::app.audiences.this-month') : (\Carbon\Carbon::create(null, (int) $value, 1)->translatedFormat('F')),
        'has_children', 'has_spouse' => trans('communications::app.audiences.'.($value === 'yes' ? 'yes' : 'no')),
        'tag' => \Illuminate\Support\Facades\DB::table('tags')->where('id', $value)->value('name'),
        'owner' => \Illuminate\Support\Facades\DB::table('users')->where('id', $value)->value('name'),
        'lead_stage' => \Illuminate\Support\Facades\DB::table('lead_pipeline_stages')->where('id', $value)->value('name'),
        'policy_status' => trans()->has('communications::app.triggers.statuses.'.$value) ? trans('communications::app.triggers.statuses.'.$value) : $value,
        'preferred_channel', 'consent' => trans()->has('communications::app.contact.channels.'.$value) ? trans('communications::app.contact.channels.'.$value) : $value,
        default => $value,
    };
@endphp
@lang('communications::app.audiences.fields.'.($rule['field'] ?? '')): {{ $display }}
