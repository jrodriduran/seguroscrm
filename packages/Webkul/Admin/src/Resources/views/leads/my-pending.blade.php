<x-admin::layouts>
    <x-slot:title>
        @lang('admin::insurance.team_radar.my_pending_title')
    </x-slot>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-5 dark:border-gray-800">
        <div class="grid gap-1">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    📋 @lang('admin::insurance.team_radar.my_pending_title')
                </h1>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                    {{ $activeLeadsCount }} @lang('admin::insurance.team_radar.active_leads_count')
                </span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                @lang('admin::insurance.team_radar.my_pending_subtitle')
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('admin.leads.team_radar.index') }}"
                class="secondary-button"
            >
                ← @lang('admin::insurance.team_radar.title')
            </a>
        </div>
    </div>

    {{-- ─── Escalated Leads (Under Master Agent Supervision) ───────────────── --}}
    @if ($escalated->isNotEmpty())
        <div class="mb-6 overflow-hidden rounded-2xl border border-red-300 bg-red-50/40 shadow-sm dark:border-red-900/60 dark:bg-red-950/10">
            <div class="flex items-center gap-2 border-b border-red-200 bg-red-100/60 px-5 py-3.5 dark:border-red-900/60 dark:bg-red-900/30">
                <span class="text-xl">🚨</span>
                <h2 class="text-base font-bold text-red-900 dark:text-red-200">
                    Casos Escalados al Agente Maestro ({{ $escalated->count() }})
                </h2>
            </div>

            <div class="divide-y divide-red-100 dark:divide-red-900/30">
                @foreach ($escalated as $lead)
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <div>
                            <a
                                href="{{ route('admin.leads.view', $lead->id) }}"
                                class="font-bold text-red-800 hover:underline dark:text-red-300"
                            >
                                {{ $lead->title ?? '#' . $lead->id }}
                            </a>
                            <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">
                                <strong>Motivo de escalación:</strong> {{ $lead->escalation_reason ?? 'SLA Vencido' }}
                                · {{ $lead->person?->name ?? '—' }}
                                @if ($lead->escalated_at)
                                    · Escalado {{ $lead->escalated_at->diffForHumans() }}
                                @endif
                            </p>
                        </div>

                        <a
                            href="{{ route('admin.leads.view', $lead->id) }}"
                            class="secondary-button !py-1 !text-xs"
                        >
                            @lang('admin::app.common.view')
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ─── Overdue Leads ──────────────────────────────────────────────────── --}}
    @if ($overdue->isNotEmpty())
        <div class="mb-6 overflow-hidden rounded-2xl border border-rose-300 bg-white shadow-sm dark:border-rose-900/60 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-rose-100 bg-rose-50/70 px-5 py-3.5 dark:border-rose-900/50 dark:bg-rose-950/20">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🔴</span>
                    <h2 class="text-base font-bold text-rose-800 dark:text-rose-300">
                        @lang('admin::insurance.team_radar.overdue') ({{ $overdue->count() }})
                    </h2>
                </div>
                <span class="text-xs text-rose-600 dark:text-rose-400">
                    Contacta o solicita intervención del Agente Maestro
                </span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($overdue as $lead)
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-rose-50/50 dark:hover:bg-rose-950/10">
                        <div>
                            <a
                                href="{{ route('admin.leads.view', $lead->id) }}"
                                class="font-bold text-rose-800 hover:underline dark:text-rose-300"
                            >
                                {{ $lead->title ?? '#' . $lead->id }}
                            </a>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Cliente: {{ $lead->person?->name ?? '—' }}
                                @if ($lead->assigned_at)
                                    · Asignado: {{ $lead->assigned_at->diffForHumans() }} (SLA: {{ $lead->sla_hours ?? 2 }}h)
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                onclick="escalateFromPending({{ $lead->id }})"
                                class="rounded-lg bg-rose-600 px-3 py-1 text-xs font-semibold text-white shadow-sm hover:bg-rose-700"
                            >
                                🚨 Escalar a MA
                            </button>

                            <a
                                href="{{ route('admin.leads.view', $lead->id) }}"
                                class="primary-button !py-1 !text-xs"
                            >
                                Gestionar
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ─── Urgent Pending Activities ──────────────────────────────────────── --}}
    @if ($urgent->isNotEmpty())
        <div class="mb-6 overflow-hidden rounded-2xl border border-amber-300 bg-white shadow-sm dark:border-amber-900/60 dark:bg-gray-900">
            <div class="flex items-center gap-2 border-b border-amber-100 bg-amber-50/70 px-5 py-3.5 dark:border-amber-900/50 dark:bg-amber-950/20">
                <span class="text-xl">🚨</span>
                <h2 class="text-base font-bold text-amber-800 dark:text-amber-300">
                    @lang('admin::insurance.team_radar.priority_urgent') – @lang('admin::app.layouts.activities') ({{ $urgent->count() }})
                </h2>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($urgent as $activity)
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-amber-50/50 dark:hover:bg-amber-950/10">
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white">
                                {{ $activity->title }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ $activity->type_name }}
                                @if ($activity->schedule_from)
                                    · Programado para: {{ $activity->schedule_from->diffForHumans() }}
                                @endif
                            </p>
                        </div>

                        @if ($activity->leads->first())
                            <a
                                href="{{ route('admin.leads.view', $activity->leads->first()->id) }}"
                                class="secondary-button !py-1 !text-xs"
                            >
                                @lang('admin::app.common.view')
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ─── Normal Pending Activities ──────────────────────────────────────── --}}
    @if ($normal->isNotEmpty())
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-2 border-b border-gray-100 p-5 dark:border-gray-800">
                <span class="text-xl">📅</span>
                <h2 class="text-base font-bold text-gray-800 dark:text-gray-200">
                    @lang('admin::insurance.team_radar.priority_normal') – @lang('admin::app.layouts.activities') ({{ $normal->count() }})
                </h2>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($normal as $activity)
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ $activity->title }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ $activity->type_name }}
                                @if ($activity->schedule_from)
                                    · Programado: {{ $activity->schedule_from->diffForHumans() }}
                                @endif
                            </p>
                        </div>

                        @if ($activity->leads->first())
                            <a
                                href="{{ route('admin.leads.view', $activity->leads->first()->id) }}"
                                class="secondary-button !py-1 !text-xs"
                            >
                                @lang('admin::app.common.view')
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ─── Empty state when everything is on track ────────────────────────── --}}
    @if ($overdue->isEmpty() && $escalated->isEmpty() && $urgent->isEmpty() && $normal->isEmpty())
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-12 text-center shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20">
            <span class="text-4xl">🎉</span>
            <h3 class="mt-3 text-lg font-bold text-emerald-900 dark:text-emerald-200">
                ¡Al día con tus leads y actividades!
            </h3>
            <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-400">
                No tienes tareas pendientes ni casos vencidos en este momento.
            </p>
        </div>
    @endif

    @pushOnce('scripts')
        <script type="module">
        window.escalateFromPending = async function (leadId) {
            const reason = prompt('Indica el motivo por el cual necesitas intervención del Agente Maestro:', 'Imposible contactar cliente / caso complejo');
            if (!reason) return;

            const res = await fetch(`{{ url(config('app.admin_path').'/leads') }}/${leadId}/escalate`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ reason }),
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            }
        };
        </script>
    @endPushOnce
</x-admin::layouts>
