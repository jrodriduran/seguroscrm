{{-- Communication sequences for this client (contact page, left panel). --}}
@php
    $cmEnrollments = \Webkul\Communications\Models\Enrollment::with('sequence:id,name')
        ->where('person_id', $person->id)
        ->latest('id')
        ->limit(8)
        ->get();
    $cmCanManage = bouncer()->hasPermission('contacts.persons.sequences');
    $cmAvailable = $cmCanManage ? \Webkul\Communications\Models\Sequence::where('is_active', true)->orderBy('name')->pluck('name', 'id') : collect();
    $cmColors = ['active' => 'tw-ok', 'paused' => 'tw-warning', 'completed' => 'tw-info', 'exited' => 'tw-overdue'];
@endphp

@if ($cmEnrollments->isNotEmpty() || $cmAvailable->isNotEmpty())
    <div class="flex w-full flex-col gap-3 border-b border-gray-300 p-4 dark:border-gray-800" v-pre>
        <h4 class="font-semibold dark:text-white">@lang('communications::app.enrollments.title')</h4>

        @foreach ($cmEnrollments as $enrollment)
            <div class="flex flex-col gap-1 rounded-lg border border-gray-200 p-2 dark:border-gray-800">
                <div class="flex items-center justify-between gap-2">
                    <span class="tw-title" style="white-space: normal;">{{ $enrollment->sequence?->name }}</span>
                    <span class="{{ $cmColors[$enrollment->status] ?? 'tw-info' }}"><span class="tw-pill">@lang('communications::app.enrollments.statuses.'.$enrollment->status)</span></span>
                </div>

                <span class="tw-meta" style="white-space: normal;">
                    @if ($enrollment->status === 'active' && $enrollment->next_run_at)
                        @lang('communications::app.enrollments.next', ['step' => $enrollment->next_position, 'when' => core()->formatDate($enrollment->next_run_at, 'd M H:i')])
                    @elseif ($enrollment->exit_reason)
                        @lang('communications::app.enrollments.reasons.'.$enrollment->exit_reason)
                    @endif
                </span>

                @if ($cmCanManage && in_array($enrollment->status, ['active', 'paused'], true))
                    <div class="flex gap-1.5">
                        @if ($enrollment->status === 'active')
                            <form method="POST" action="{{ route('admin.communications.enrollments.pause', $enrollment->id) }}">@csrf<button type="submit" class="tw-btn">@lang('communications::app.enrollments.pause')</button></form>
                        @else
                            <form method="POST" action="{{ route('admin.communications.enrollments.resume', $enrollment->id) }}">@csrf<button type="submit" class="tw-btn tw-btn-primary">@lang('communications::app.enrollments.resume')</button></form>
                        @endif

                        <form method="POST" action="{{ route('admin.communications.enrollments.stop', $enrollment->id) }}" onsubmit="return confirm(@js(trans('communications::app.enrollments.stop-confirm')));">@csrf<button type="submit" class="tw-btn tw-overdue">@lang('communications::app.enrollments.stop')</button></form>
                    </div>
                @endif
            </div>
        @endforeach

        @if ($cmAvailable->isNotEmpty())
            <form method="POST" action="{{ route('admin.communications.enrollments.store', $person->id) }}" class="flex gap-1.5">
                @csrf
                <select name="sequence_id" class="tw-input" aria-label="@lang('communications::app.enrollments.enroll')">
                    @foreach ($cmAvailable as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="tw-btn tw-btn-primary">@lang('communications::app.enrollments.enroll')</button>
            </form>
        @endif
    </div>
@endif
