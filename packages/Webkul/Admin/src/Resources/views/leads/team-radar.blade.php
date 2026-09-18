<x-admin::layouts>
    <x-slot:title>
        @lang('admin::insurance.team_radar.title')
    </x-slot>

    {{-- ─── Header & Pipeline Controls ─────────────────────────────────────── --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-5 dark:border-gray-800">
        <div class="grid gap-1">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    @lang('admin::insurance.team_radar.title')
                </h1>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-blue-500"></span>
                    {{ $assignmentRule->strategy === 'round_robin' ? '🔄 Round Robin' : ($assignmentRule->strategy === 'least_loaded' ? '⚖️ Menor Carga' : '✍️ Manual') }}
                </span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                @lang('admin::insurance.team_radar.subtitle')
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            {{-- Pipeline Filter Form --}}
            <form method="GET" action="{{ route('admin.leads.team_radar.index') }}" class="flex items-center gap-2">
                <label for="pipeline_id" class="text-xs font-medium text-gray-500 dark:text-gray-400">Pipeline:</label>
                <select
                    name="pipeline_id"
                    id="pipeline_id"
                    onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                >
                    @foreach($pipelines as $pipeline)
                        <option value="{{ $pipeline->id }}" {{ $pipeline->id == $selectedPipelineId ? 'selected' : '' }}>
                            {{ $pipeline->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            <button
                type="button"
                onclick="document.getElementById('rules-modal').classList.remove('hidden')"
                class="primary-button flex items-center gap-2 !py-1.5 !text-sm"
            >
                ⚙️ @lang('admin::insurance.team_radar.configure_rules')
            </button>

            <a
                href="{{ route('admin.leads.index') }}"
                class="secondary-button !py-1.5 !text-sm"
            >
                ← @lang('admin::app.leads.index.title')
            </a>
        </div>
    </div>

    {{-- ─── Global SLA Counter Cards ────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
        <div class="rounded-2xl border border-red-200 bg-gradient-to-br from-red-50 to-white p-4 shadow-sm dark:border-red-900/50 dark:from-red-950/20 dark:to-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-red-600 dark:text-red-400">
                🚨 @lang('admin::insurance.team_radar.escalated')
            </p>
            <p class="mt-2 text-3xl font-extrabold text-red-700 dark:text-red-400">
                {{ $escalatedLeads->count() }}
            </p>
            <p class="mt-1 text-xs text-red-500">Requieren intervención MA</p>
        </div>

        <div class="rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-4 shadow-sm dark:border-rose-900/50 dark:from-rose-950/20 dark:to-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">
                🔴 @lang('admin::insurance.team_radar.overdue')
            </p>
            <p class="mt-2 text-3xl font-extrabold text-rose-700 dark:text-rose-400" id="stat-overdue">
                —
            </p>
            <p class="mt-1 text-xs text-rose-500">Sin contacto en ventana SLA</p>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 shadow-sm dark:border-amber-900/50 dark:from-amber-950/20 dark:to-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                🟡 @lang('admin::insurance.team_radar.pending_contact')
            </p>
            <p class="mt-2 text-3xl font-extrabold text-amber-700 dark:text-amber-400" id="stat-pending">
                —
            </p>
            <p class="mt-1 text-xs text-amber-500">En reloj de primer contacto</p>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-4 shadow-sm dark:border-emerald-900/50 dark:from-emerald-950/20 dark:to-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                🟢 @lang('admin::insurance.team_radar.on_time')
            </p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-700 dark:text-emerald-400" id="stat-active">
                —
            </p>
            <p class="mt-1 text-xs text-emerald-500">Contactados en tiempo</p>
        </div>

        <div class="rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-4 shadow-sm dark:border-blue-900/50 dark:from-blue-950/20 dark:to-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                🏆 @lang('admin::insurance.team_radar.policies_issued')
            </p>
            <p class="mt-2 text-3xl font-extrabold text-blue-700 dark:text-blue-400" id="stat-policies">
                —
            </p>
            <p class="mt-1 text-xs text-blue-500">Pólizas cerradas ganadas hoy</p>
        </div>
    </div>

    {{-- ─── Workload & Capacity Cards Per Agent ─────────────────────────────── --}}
    <div class="mb-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-800 dark:text-gray-100">
                👥 @lang('admin::insurance.team_radar.workload')
            </h2>
            <span class="text-xs text-gray-500">
                Tope configurado: {{ $assignmentRule->max_capacity ? $assignmentRule->max_capacity.' leads/agente' : 'Sin límite' }}
            </span>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($agents as $agent)
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                {{ strtoupper(substr($agent->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $agent->name }}</p>
                                <p class="text-xs text-gray-400">{{ $agent->email }}</p>
                            </div>
                        </div>

                        @if($agent->escalated_leads > 0)
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                🚨 {{ $agent->escalated_leads }}
                            </span>
                        @endif
                    </div>

                    {{-- Capacity progress bar --}}
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-gray-600 dark:text-gray-400">Leads Activos</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">
                                {{ $agent->active_leads }}
                                @if($agent->max_capacity > 0)
                                    / {{ $agent->max_capacity }} ({{ $agent->capacity_percent }}%)
                                @endif
                            </span>
                        </div>
                        <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            @php
                                $barColor = $agent->capacity_percent >= 90 ? 'bg-red-500' : ($agent->capacity_percent >= 70 ? 'bg-amber-500' : 'bg-blue-600');
                            @endphp
                            <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ min(100, $agent->capacity_percent ?: 25) }}%"></div>
                        </div>
                    </div>

                    {{-- Mini stats row --}}
                    <div class="mt-3.5 flex items-center justify-between border-t border-gray-100 pt-2.5 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        <span>🔴 {{ $agent->overdue_leads }} vencidos</span>
                        <span>🟡 {{ $agent->pending_leads }} pendientes</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ─── Escalated Leads Requiring Master Agent Attention ────────────────── --}}
    @if($escalatedLeads->isNotEmpty())
        <div class="mb-6 overflow-hidden rounded-2xl border border-red-300 bg-red-50/40 shadow-sm dark:border-red-900/70 dark:bg-red-950/10">
            <div class="flex items-center justify-between border-b border-red-200 bg-red-100/60 px-5 py-3.5 dark:border-red-900/60 dark:bg-red-900/30">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🚨</span>
                    <h2 class="text-base font-bold text-red-900 dark:text-red-200">
                        @lang('admin::insurance.team_radar.escalated_leads_title') ({{ $escalatedLeads->count() }})
                    </h2>
                </div>
                <span class="text-xs font-medium text-red-700 dark:text-red-300">
                    Intervención prioritaria del Agente Maestro requerida
                </span>
            </div>

            <div class="divide-y divide-red-100 dark:divide-red-900/30">
                @foreach($escalatedLeads as $lead)
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.leads.view', $lead->id) }}" class="font-bold text-red-800 hover:underline dark:text-red-300">
                                    {{ $lead->title ?? '#'.$lead->id }}
                                </a>
                                @if($lead->pipeline)
                                    <span class="rounded bg-red-200/70 px-1.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/50 dark:text-red-200">
                                        {{ $lead->pipeline->name }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                <strong>Motivo:</strong> {{ $lead->escalation_reason ?? 'Incumplimiento de SLA' }}
                                · Asignado a: {{ $lead->user?->name ?? 'Sin asignar' }}
                                @if($lead->escalated_at)
                                    · Escalado: {{ $lead->escalated_at->diffForHumans() }}
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            {{-- Reassign dropdown --}}
                            <select
                                onchange="reassignSingleLead({{ $lead->id }}, this.value)"
                                class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            >
                                <option value="">Reasignar a...</option>
                                @foreach($agents as $ag)
                                    <option value="{{ $ag->id }}">{{ $ag->name }}</option>
                                @endforeach
                            </select>

                            <button
                                onclick="resolveEscalation({{ $lead->id }})"
                                class="rounded-lg bg-emerald-600 px-3 py-1 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700"
                            >
                                ✅ @lang('admin::insurance.team_radar.resolve')
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ─── Unassigned Leads Panel ─────────────────────────────────────────── --}}
    @if($unassignedLeads->isNotEmpty())
        <div class="mb-6 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/40 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/10">
            <div class="flex items-center justify-between border-b border-amber-200 bg-amber-100/60 px-5 py-3.5 dark:border-amber-900/60 dark:bg-amber-900/30">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📥</span>
                    <h2 class="text-base font-bold text-amber-900 dark:text-amber-200">
                        @lang('admin::insurance.team_radar.unassigned_leads_title') ({{ $unassignedLeads->count() }})
                    </h2>
                </div>
                <span class="text-xs font-medium text-amber-700 dark:text-amber-300">
                    Distribuye manualmente o por estrategia
                </span>
            </div>

            <div class="divide-y divide-amber-100 dark:divide-amber-900/30">
                @foreach($unassignedLeads as $lead)
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-amber-50 dark:hover:bg-amber-900/20">
                        <div>
                            <a href="{{ route('admin.leads.view', $lead->id) }}" class="font-bold text-amber-900 hover:underline dark:text-amber-200">
                                {{ $lead->title ?? '#'.$lead->id }}
                            </a>
                            <p class="mt-0.5 text-xs text-gray-500">
                                Cliente: {{ $lead->person?->name ?? '—' }} · Creado: {{ $lead->created_at->diffForHumans() }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <select
                                id="unassigned-agent-{{ $lead->id }}"
                                class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            >
                                <option value="">Seleccionar agente...</option>
                                @foreach($agents as $ag)
                                    <option value="{{ $ag->id }}">{{ $ag->name }}</option>
                                @endforeach
                            </select>

                            <button
                                onclick="assignUnassignedLead({{ $lead->id }})"
                                class="primary-button !py-1 !text-xs"
                            >
                                Asignar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ─── Agent Table with Bulk Reassign & DataGrid ───────────────────────── --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 p-5 dark:border-gray-800">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                @lang('admin::insurance.team_radar.team_status')
            </h2>

            {{-- Bulk Reassign Bar --}}
            <div class="flex items-center gap-2" id="bulk-reassign-bar">
                <span class="text-sm font-medium text-gray-500">
                    <span id="selected-count">0</span> @lang('admin::insurance.team_radar.leads_selected')
                </span>

                <select
                    id="reassign-agent-select"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                >
                    <option value="">— @lang('admin::insurance.team_radar.select_agent') —</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                    @endforeach
                </select>

                <button
                    id="btn-bulk-reassign"
                    class="primary-button !py-1.5 !text-sm"
                >
                    @lang('admin::insurance.team_radar.bulk_reassign')
                </button>
            </div>
        </div>

        {{-- DataGrid --}}
        <x-admin::datagrid :src="route('admin.leads.team_radar.data_grid')" />
    </div>

    {{-- ─── Overdue Leads Real-Time Alert Panel ─────────────────────────────── --}}
    <div class="rounded-2xl border border-rose-200 bg-white shadow-sm dark:border-rose-900/60 dark:bg-gray-900">
        <div class="flex items-center gap-2 border-b border-rose-100 p-5 dark:border-rose-900/50">
            <span class="text-xl">⚠️</span>
            <h3 class="text-base font-bold text-rose-800 dark:text-rose-300">
                @lang('admin::insurance.team_radar.overdue_alert_title')
            </h3>
        </div>

        <div id="overdue-leads-list" class="divide-y divide-gray-100 dark:divide-gray-800">
            <div class="p-6 text-center text-sm text-gray-400">
                @lang('admin::insurance.team_radar.loading')
            </div>
        </div>
    </div>

    {{-- ─── Modal: Workflow & SLA Rules Configuration ──────────────────────── --}}
    <div id="rules-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 p-5 dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    ⚙️ Configurar Workflow, Asignación y SLA
                </h3>
                <button onclick="document.getElementById('rules-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    ✕
                </button>
            </div>

            <form id="rules-form" onsubmit="saveAllRules(event)" class="p-6">
                <input type="hidden" name="lead_pipeline_id" value="{{ $selectedPipelineId }}">

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- Assignment Strategy --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            @lang('admin::insurance.team_radar.assignment_strategy')
                        </label>
                        <select
                            name="strategy"
                            class="mt-2 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                            <option value="round_robin" {{ ($assignmentRule->strategy ?? '') === 'round_robin' ? 'selected' : '' }}>
                                @lang('admin::insurance.team_radar.strategy_round_robin')
                            </option>
                            <option value="least_loaded" {{ ($assignmentRule->strategy ?? '') === 'least_loaded' ? 'selected' : '' }}>
                                @lang('admin::insurance.team_radar.strategy_least_loaded')
                            </option>
                            <option value="manual" {{ ($assignmentRule->strategy ?? '') === 'manual' ? 'selected' : '' }}>
                                @lang('admin::insurance.team_radar.strategy_manual')
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-400">El Master Agent define cómo se distribuyen los nuevos casos.</p>
                    </div>

                    {{-- Max Capacity --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            @lang('admin::insurance.team_radar.max_capacity')
                        </label>
                        <input
                            type="number"
                            name="max_capacity"
                            min="0"
                            value="{{ $assignmentRule->max_capacity ?? 0 }}"
                            class="mt-2 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                        <p class="mt-1 text-xs text-gray-400">0 = sin límite de carga por agente.</p>
                    </div>

                    {{-- SLA First Contact Hours --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            @lang('admin::insurance.team_radar.first_contact_hours')
                        </label>
                        <input
                            type="number"
                            name="first_contact_hours"
                            min="1"
                            value="{{ $slaRules->first()?->first_contact_hours ?? 2 }}"
                            class="mt-2 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                        <p class="mt-1 text-xs text-gray-400">Horas permitidas para realizar el primer contacto.</p>
                    </div>

                    {{-- SLA Escalation Hours --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            @lang('admin::insurance.team_radar.escalation_hours')
                        </label>
                        <input
                            type="number"
                            name="escalation_hours"
                            min="1"
                            value="{{ $slaRules->first()?->escalation_hours ?? 4 }}"
                            class="mt-2 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                        <p class="mt-1 text-xs text-gray-400">Horas tras vencer antes de alertar y escalar al MA.</p>
                    </div>

                    {{-- Follow-up hours --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            @lang('admin::insurance.team_radar.follow_up_hours')
                        </label>
                        <input
                            type="number"
                            name="follow_up_hours"
                            min="1"
                            value="{{ $slaRules->first()?->follow_up_hours ?? 24 }}"
                            class="mt-2 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                    </div>

                    {{-- Pool of Active Agents --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            Agentes Elegibles para Distribución en este Pipeline
                        </label>
                        <div class="mt-2 max-h-40 overflow-y-auto rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            @php
                                $currentPool = $assignmentRule->agent_ids ?? [];
                            @endphp
                            @foreach($agents as $ag)
                                <label class="flex items-center gap-2.5 py-1 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="agent_ids[]"
                                        value="{{ $ag->id }}"
                                        {{ empty($currentPool) || in_array($ag->id, $currentPool) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700"
                                    >
                                    <span>{{ $ag->name }} ({{ $ag->email }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-800">
                    <button
                        type="button"
                        onclick="document.getElementById('rules-modal').classList.add('hidden')"
                        class="secondary-button"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        💾 @lang('admin::insurance.team_radar.save_rules')
                    </button>
                </div>
            </form>
        </div>
    </div>

    @pushOnce('scripts')
        <script type="module">
        (function () {
            // ── Load overdue leads panel ──────────────────────────────────────
            async function loadOverdueLeads () {
                const res  = await fetch('{{ route('admin.leads.index') }}?sla_status=overdue&format=json');
                const data = await res.json().catch(() => null);

                const list = document.getElementById('overdue-leads-list');
                if (! data || ! data.data?.length) {
                    list.innerHTML = `<div class="p-6 text-center text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                        ✅ @lang('admin::insurance.team_radar.no_overdue')</div>`;
                    return;
                }

                list.innerHTML = data.data.slice(0, 10).map(lead => {
                    const hrs = lead.assigned_at
                        ? Math.floor((Date.now() - new Date(lead.assigned_at)) / 36e5)
                        : '?';

                    return `
                    <div class="flex items-center justify-between p-4 hover:bg-rose-50/50 dark:hover:bg-rose-950/10">
                        <div>
                            <a href="/{{ config('app.admin_path') }}/leads/view/${lead.id}"
                               class="font-bold text-rose-800 hover:underline dark:text-rose-300">
                                ${lead.title ?? '#' + lead.id}
                            </a>
                            <p class="text-xs text-gray-500">${lead.sales_person ?? '—'} · ${hrs}h @lang('admin::insurance.team_radar.inactive')</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                class="secondary-button !py-1 !text-xs"
                                onclick="window.urgentFlag(${lead.id})"
                            >
                                🚩 @lang('admin::insurance.team_radar.urgent_flag')
                            </button>
                            <button
                                class="rounded-lg bg-rose-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-rose-700"
                                onclick="window.escalateLeadQuick(${lead.id})"
                            >
                                🚨 Escalar a MA
                            </button>
                        </div>
                    </div>`;
                }).join('');

                document.getElementById('stat-overdue').textContent  = data.data.filter(l => l.sla_status === 'overdue').length;
                document.getElementById('stat-pending').textContent  = data.data.filter(l => l.sla_status === 'pending').length;
                document.getElementById('stat-active').textContent   = data.data.filter(l => l.sla_status === 'active').length;
                document.getElementById('stat-policies').textContent = data.data.filter(l => l.stage_code === 'won').length;
            }

            loadOverdueLeads();
            setInterval(loadOverdueLeads, 60_000);

            // ── Urgent flag toggle ────────────────────────────────────────────
            window.urgentFlag = async function (leadId) {
                const res = await fetch(`{{ url(config('app.admin_path').'/leads') }}/${leadId}/urgent-flag`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                }
            };

            // ── Escalate Lead Quick ───────────────────────────────────────────
            window.escalateLeadQuick = async function (leadId) {
                const reason = prompt('Motivo de escalación al Agente Maestro:', 'SLA Vencido - Solicitud de intervención');
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
                    window.location.reload();
                }
            };

            // ── Reassign single lead ──────────────────────────────────────────
            window.reassignSingleLead = async function (leadId, newUserId) {
                if (!newUserId) return;

                const res = await fetch('{{ route('admin.leads.assign_manual') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ lead_id: leadId, new_user_id: newUserId }),
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                }
            };

            // ── Assign unassigned lead ────────────────────────────────────────
            window.assignUnassignedLead = async function (leadId) {
                const newUserId = document.getElementById(`unassigned-agent-${leadId}`).value;
                if (!newUserId) {
                    alert('Por favor selecciona un agente.');
                    return;
                }
                await window.reassignSingleLead(leadId, newUserId);
            };

            // ── Resolve escalation ────────────────────────────────────────────
            window.resolveEscalation = async function (leadId) {
                const res = await fetch(`{{ url(config('app.admin_path').'/leads') }}/${leadId}/resolve-escalation`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                }
            };

            // ── Bulk Reassign ─────────────────────────────────────────────────
            document.getElementById('btn-bulk-reassign')?.addEventListener('click', async () => {
                const newUserId = document.getElementById('reassign-agent-select').value;
                const checked   = [...document.querySelectorAll('.datagrid-row-check:checked')];
                const leadIds   = checked.map(cb => cb.value);

                if (! newUserId || leadIds.length === 0) {
                    alert('Por favor selecciona los leads y el agente de destino.');
                    return;
                }

                const res = await fetch('{{ route('admin.leads.bulk_reassign') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ lead_ids: leadIds, new_user_id: newUserId }),
                });

                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                }
            });

            // ── Save Workflow & SLA Rules ─────────────────────────────────────
            window.saveAllRules = async function (event) {
                event.preventDefault();
                const form = event.target;
                const formData = new FormData(form);

                const agentIds = [];
                form.querySelectorAll('input[name="agent_ids[]"]:checked').forEach(cb => {
                    agentIds.push(parseInt(cb.value));
                });

                const pipelineId = parseInt(formData.get('lead_pipeline_id'));

                // 1. Save Assignment Rule
                const assignRes = await fetch('{{ route('admin.leads.assignment_rules.save') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        lead_pipeline_id: pipelineId,
                        strategy: formData.get('strategy'),
                        max_capacity: parseInt(formData.get('max_capacity')),
                        agent_ids: agentIds,
                    }),
                });

                // 2. Save SLA Rule
                const slaRes = await fetch('{{ route('admin.leads.sla_rules.save') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        lead_pipeline_id: pipelineId,
                        first_contact_hours: parseInt(formData.get('first_contact_hours')),
                        follow_up_hours: parseInt(formData.get('follow_up_hours')),
                        escalation_hours: parseInt(formData.get('escalation_hours')),
                    }),
                });

                const aData = await assignRes.json();
                const sData = await slaRes.json();

                if (aData.success && sData.success) {
                    window.location.reload();
                } else {
                    alert('Error guardando la configuración.');
                }
            };
        })();
        </script>
    @endPushOnce
</x-admin::layouts>
