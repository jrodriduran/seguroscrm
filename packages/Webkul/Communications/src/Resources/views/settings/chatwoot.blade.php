<x-admin::layouts>
    <x-slot:title>
        @lang('communications::app.chatwoot.title')
    </x-slot>

    <div class="flex flex-col gap-4" v-pre>
        <div class="flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="settings.communications.chatwoot" />

                <div class="text-xl font-bold dark:text-white">@lang('communications::app.chatwoot.title')</div>
            </div>

            <span class="{{ $configured && ! $error ? 'tw-ok' : 'tw-warning' }}">
                <span class="tw-pill">{{ $configured && ! $error ? trans('communications::app.chatwoot.status-connected') : trans('communications::app.chatwoot.status-not-connected') }}</span>
            </span>
        </div>

        <div class="tw-card tw-info p-4 text-sm text-gray-700 dark:text-gray-300">
            <p>@lang('communications::app.chatwoot.info')</p>
        </div>

        @if ($error)
            <div class="tw-card tw-overdue p-4 text-sm">{{ $error }}</div>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            {{-- Connection --}}
            <section class="tw-card tw-info lg:col-span-2">
                <div class="tw-card-head">
                    <span class="tw-card-title">@lang('communications::app.chatwoot.connection')</span>
                </div>

                <form method="POST" action="{{ route('admin.settings.communications.chatwoot.update') }}" class="flex flex-col gap-3 p-4">
                    @csrf

                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <label class="tw-label">@lang('communications::app.chatwoot.base-url')</label>
                            <input type="url" name="base_url" required class="tw-input" value="{{ old('base_url', $values['base_url']) }}" placeholder="https://chat.example.com">
                        </div>

                        <div>
                            <label class="tw-label">@lang('communications::app.chatwoot.account-id')</label>
                            <input type="number" name="account_id" min="1" required class="tw-input" value="{{ old('account_id', $values['account_id']) }}">
                        </div>
                    </div>

                    <div>
                        <label class="tw-label">@lang('communications::app.chatwoot.api-token')</label>
                        <input type="password" name="api_token" autocomplete="new-password" class="tw-input" placeholder="{{ $hasToken ? trans('communications::app.chatwoot.token-saved') : trans('communications::app.chatwoot.token-placeholder') }}">
                        <p class="tw-meta mt-1" style="white-space: normal;">@lang('communications::app.chatwoot.token-help')</p>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="tw-label">@lang('communications::app.chatwoot.owner')</label>
                            <select name="owner_id" class="tw-input">
                                <option value="">@lang('communications::app.chatwoot.owner-default')</option>
                                @foreach ($users as $id => $name)
                                    <option value="{{ $id }}" @selected((string) old('owner_id', $values['owner_id']) === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="tw-label">@lang('communications::app.chatwoot.default-inbox')</label>
                            <select name="default_inbox_id" class="tw-input">
                                <option value="">—</option>
                                @foreach ($inboxes as $inbox)
                                    <option value="{{ $inbox['id'] }}" @selected((string) old('default_inbox_id', $values['default_inbox_id']) === (string) $inbox['id'])>{{ $inbox['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="tw-label">@lang('communications::app.chatwoot.pipeline')</label>
                            <select name="pipeline_id" class="tw-input">
                                <option value="">@lang('communications::app.chatwoot.pipeline-default')</option>
                                @foreach ($pipelines as $id => $name)
                                    <option value="{{ $id }}" @selected((string) old('pipeline_id', $values['pipeline_id']) === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="tw-label">@lang('communications::app.chatwoot.source')</label>
                            <select name="lead_source_id" class="tw-input">
                                <option value="">—</option>
                                @foreach ($sources as $id => $name)
                                    <option value="{{ $id }}" @selected((string) old('lead_source_id', $values['lead_source_id']) === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($errors->any())
                        <p class="text-xs text-red-600">{{ $errors->first() }}</p>
                    @endif

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="tw-btn tw-btn-primary">@lang('communications::app.chatwoot.save')</button>
                        <span class="tw-meta" style="white-space: normal;">@lang('communications::app.chatwoot.save-help')</span>
                    </div>
                </form>

                @if ($hasToken)
                    <form method="POST" action="{{ route('admin.settings.communications.chatwoot.disconnect') }}" class="px-4 pb-4" onsubmit="return confirm(@js(trans('communications::app.chatwoot.disconnect-confirm')));">
                        @csrf
                        <button type="submit" class="tw-btn tw-overdue">@lang('communications::app.chatwoot.disconnect')</button>
                    </form>
                @endif
            </section>

            {{-- Status --}}
            <div class="flex flex-col gap-4">
                <section class="tw-card tw-ok">
                    <div class="tw-card-head">
                        <span class="tw-card-title">@lang('communications::app.chatwoot.inboxes')</span>
                        <span class="tw-count">{{ count($inboxes) }}</span>
                    </div>

                    @forelse ($inboxes as $inbox)
                        <div class="tw-row" style="grid-template-columns: minmax(0, 1fr) auto;">
                            <span class="tw-title">{{ $inbox['name'] }}</span>
                            <span class="tw-info"><span class="tw-pill">@lang('communications::app.chatwoot.channels.'.$inbox['channel'])</span></span>
                        </div>
                    @empty
                        <p class="tw-empty">@lang('communications::app.chatwoot.no-inboxes')</p>
                    @endforelse
                </section>

                <section class="tw-card tw-info p-4 text-sm">
                    <span class="tw-label">@lang('communications::app.chatwoot.webhook')</span>
                    <p class="tw-meta" style="white-space: normal;">
                        @if ($webhookRegisteredAt)
                            @lang('communications::app.chatwoot.webhook-registered', ['when' => $webhookRegisteredAt])
                        @else
                            @lang('communications::app.chatwoot.webhook-pending')
                        @endif
                    </p>
                    <p class="tw-meta mt-2" style="white-space: normal;">@lang('communications::app.chatwoot.messages-logged', ['count' => $messageCount])</p>
                </section>
            </div>
        </div>
    </div>
</x-admin::layouts>
