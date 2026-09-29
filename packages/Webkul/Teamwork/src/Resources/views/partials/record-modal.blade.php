{{-- Follow-up dialog for the record shown on the current page, if any. --}}
@php
    $twEntities = app(\Webkul\Teamwork\Services\Entities::class);
    $twRecord = $twEntities->fromCurrentRoute();
@endphp

@if ($twRecord)
    @include('teamwork::partials.modal', [
        'entityType' => $twRecord[0],
        'entityId' => $twRecord[1],
        'recordTitle' => $twEntities->describe($twRecord[0], $twRecord[1])['title'] ?? null,
    ])

    @include('teamwork::partials.note-modal', [
        'entityType' => $twRecord[0],
        'entityId' => $twRecord[1],
        'recordTitle' => $twEntities->describe($twRecord[0], $twRecord[1])['title'] ?? null,
    ])

    @if ($twRecord[0] === 'lead' && (auth()->guard('user')->user()->role?->permission_type === 'all' || bouncer()->hasPermission('leads.edit')))
        @include('teamwork::partials.handoff-modal')
    @endif
@endif
