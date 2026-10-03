<x-admin::layouts>
    <x-slot:title>
        @lang('communications::app.deliveries.title')
    </x-slot>

    @php
        $cmIcons = ['card' => '💌', 'gift_card' => '🎁', 'kit' => '📦', 'flowers' => '💐', 'other' => '📮'];
        $cmTones = ['requested' => 'tw-warning', 'approved' => 'tw-info', 'purchased' => 'tw-info', 'sent' => 'tw-ok', 'delivered' => 'tw-ok', 'cancelled' => 'tw-overdue'];
    @endphp

    <div class="flex flex-col gap-4" v-pre>
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <x-admin::breadcrumbs name="communications.deliveries" />
                <div class="text-xl font-bold dark:text-white">@lang('communications::app.deliveries.title')</div>
            </div>

            <form method="GET" class="flex items-center gap-2">
                <input type="month" name="month" value="{{ $month }}" class="tw-input" style="width: auto;" onchange="this.form.submit()">
            </form>
        </div>

        <div class="tw-card tw-info p-4 text-sm text-gray-700 dark:text-gray-300">
            <p>@lang('communications::app.deliveries.info', ['limit' => number_format($limit, 2)])</p>
        </div>

        {{-- Board --}}
        <div class="grid gap-3 lg:grid-cols-4">
            @foreach ($columns as $status => $items)
                <section class="tw-card {{ $cmTones[$status] }}">
                    <div class="tw-card-head">
                        <span class="tw-card-title">@lang('communications::app.deliveries.statuses.'.$status)</span>
                        <span class="tw-count">{{ $items->count() }}</span>
                    </div>

                    <div class="flex flex-col gap-2 p-3">
                        @forelse ($items as $delivery)
                            <div class="cm-delivery {{ $focus === $delivery->id ? 'is-focus' : '' }}" id="delivery-{{ $delivery->id }}">
                                <div class="flex items-start justify-between gap-2">
                                    <a href="{{ route('admin.contacts.persons.view', $delivery->person_id) }}" class="tw-title" style="white-space: normal;">{{ $cmIcons[$delivery->item] ?? '📮' }} {{ $names[$delivery->person_id] ?? '#'.$delivery->person_id }}</a>
                                    @if ($showMoney && $delivery->cost)
                                        <span class="tw-meta">${{ number_format((float) $delivery->cost, 2) }}</span>
                                    @endif
                                </div>

                                <span class="tw-meta" style="white-space: normal;">@lang('communications::app.deliveries.items.'.$delivery->item){{ $delivery->note ? ' — '.$delivery->note : '' }}</span>
                                <span class="tw-meta" style="white-space: normal;">{{ $delivery->addressLine() ? '📍 '.$delivery->addressLine() : '⚠ '.trans('communications::app.sequences.no-address') }}</span>
                                <span class="tw-meta">👤 {{ $delivery->assignee?->name ?? '—' }} · {{ core()->formatDate($delivery->created_at, 'd M') }}{{ $delivery->tracking ? ' · #'.$delivery->tracking : '' }}</span>

                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach ($flow[$delivery->status] as $next)
                                        @if ($next === 'approved' && ! $isOwner)
                                            <span class="tw-meta">@lang('communications::app.deliveries.needs-owner')</span>
                                            @continue
                                        @endif

                                        <form method="POST" action="{{ route('admin.communications.deliveries.move', $delivery->id) }}" class="flex gap-1" @if ($next === 'cancelled') onsubmit="return confirm(@js(trans('communications::app.deliveries.cancel-confirm')));" @endif>
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $next }}">
                                            @if ($next === 'sent')
                                                <input type="text" name="tracking" maxlength="120" class="tw-input" style="height: 30px; width: 110px;" placeholder="@lang('communications::app.deliveries.tracking')">
                                            @endif
                                            <button type="submit" class="tw-btn {{ $next === 'cancelled' ? 'tw-overdue' : 'tw-btn-primary' }}" style="height: 30px;">@lang('communications::app.deliveries.actions.'.$next)</button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="tw-meta">@lang('communications::app.deliveries.empty-column')</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            {{-- Done this month --}}
            <section class="tw-card tw-ok lg:col-span-2">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('communications::app.deliveries.closed')</span>
                    <span class="tw-count">{{ $closed->count() }}</span>
                </div>

                @forelse ($closed as $delivery)
                    <div class="tw-row" style="grid-template-columns: minmax(0, 1fr) auto auto;">
                        <a href="{{ route('admin.contacts.persons.view', $delivery->person_id) }}" class="tw-title">{{ $cmIcons[$delivery->item] ?? '📮' }} {{ $names[$delivery->person_id] ?? '#'.$delivery->person_id }}</a>
                        <span class="tw-meta">{{ core()->formatDate($delivery->delivered_at ?? $delivery->updated_at, 'd M') }}{{ $showMoney && $delivery->cost ? ' · $'.number_format((float) $delivery->cost, 2) : '' }}</span>
                        <span class="{{ $cmTones[$delivery->status] }}"><span class="tw-pill">@lang('communications::app.deliveries.statuses.'.$delivery->status)</span></span>
                    </div>
                @empty
                    <p class="tw-empty">@lang('communications::app.deliveries.none-closed')</p>
                @endforelse
            </section>

            <div class="flex flex-col gap-4">
                @if ($showMoney)
                    <section class="tw-card tw-info">
                        <div class="tw-card-head">
                            <span class="tw-card-title">@lang('communications::app.deliveries.spend')</span>
                            <span class="tw-meta">${{ number_format((float) $spend->sum('amount'), 2) }}</span>
                        </div>

                        @forelse ($spend as $row)
                            <div class="tw-row" style="grid-template-columns: minmax(0, 1fr) auto;">
                                <span class="tw-title">{{ $row->agent }}</span>
                                <span class="tw-meta">{{ $row->total }} · ${{ number_format((float) $row->amount, 2) }}</span>
                            </div>
                        @empty
                            <p class="tw-empty">—</p>
                        @endforelse
                    </section>
                @endif

                @if ($isOwner)
                    <section class="tw-card tw-info p-4">
                        <span class="tw-label">@lang('communications::app.deliveries.settings')</span>

                        <form method="POST" action="{{ route('admin.communications.deliveries.settings') }}" class="flex flex-col gap-2">
                            @csrf
                            <label class="tw-meta">@lang('communications::app.deliveries.approval-over')</label>
                            <input type="number" name="approval_over" min="0" step="0.01" class="tw-input" value="{{ $limit }}">

                            <label class="tw-meta">@lang('communications::app.deliveries.default-assignee')</label>
                            <select name="assignee" class="tw-input">
                                <option value="">@lang('communications::app.deliveries.auto-assignee')</option>
                                @foreach ($users as $id => $name)
                                    <option value="{{ $id }}" @selected((string) $assignee === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>

                            <button type="submit" class="tw-btn tw-btn-primary self-start">@lang('communications::app.templates.save')</button>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-admin::layouts>
