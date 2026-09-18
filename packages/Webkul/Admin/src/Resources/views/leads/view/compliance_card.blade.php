@php
    $consent = $lead->consent;
    $dmiDocuments = $lead->dmiDocuments;
    $criticalDmi = $dmiDocuments->where('urgency_level', 'critical')->first();
    $warningDmi = $dmiDocuments->where('urgency_level', 'warning')->first();
    $pendingUploadDmi = $dmiDocuments->where('status', 'pending_upload')->count();
@endphp

<div class="flex w-full flex-col gap-3 border-b border-gray-300 p-4 dark:border-gray-800 bg-slate-50/50 dark:bg-gray-950/40">
    <div class="flex items-center justify-between">
        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
            <span>🛡️</span> Cumplimiento ACA & Healthcare.gov
        </h4>
        <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-300 font-mono">
            CMS 45 CFR
        </span>
    </div>

    <!-- CMS Consent Row -->
    <div class="flex items-center justify-between p-2.5 rounded-lg border bg-white dark:bg-gray-900 {{ $consent?->status === 'signed' ? 'border-emerald-200 dark:border-emerald-800' : 'border-amber-200 dark:border-amber-800' }}">
        <div class="space-y-0.5">
            <span class="text-[11px] text-gray-400 block font-medium">Consentimiento Digital:</span>
            @if ($consent?->status === 'signed')
                <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                    <span>✓</span> Firmado ({{ $consent->signed_at ? $consent->signed_at->format('d/m/y') : 'Sí' }})
                </div>
            @else
                <div class="flex items-center gap-1.5 text-xs font-bold text-amber-700 dark:text-amber-400">
                    <span>⏳</span> Pendiente de Firma
                </div>
            @endif
        </div>

        @if ($consent?->status === 'signed')
            <a
                href="{{ route('admin.leads.consent.certificate', $lead->id) }}"
                target="_blank"
                class="text-[11px] font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 px-2 py-1 rounded bg-blue-50 dark:bg-blue-950"
            >
                Ver PDF
            </a>
        @else
            <a
                href="?tab=consent"
                class="text-[11px] font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 px-2 py-1 rounded bg-emerald-50 dark:bg-emerald-950 flex items-center gap-1"
            >
                <span>📲</span> Enviar
            </a>
        @endif
    </div>

    <!-- DMI Status Row -->
    <div class="flex items-center justify-between p-2.5 rounded-lg border bg-white dark:bg-gray-900 {{ $criticalDmi ? 'border-rose-300 bg-rose-50/20 dark:border-rose-900' : ($warningDmi ? 'border-amber-200 dark:border-amber-800' : 'border-gray-200 dark:border-gray-800') }}">
        <div class="space-y-0.5">
            <span class="text-[11px] text-gray-400 block font-medium">Inconsistencias DMI (90d):</span>
            @if ($criticalDmi)
                <div class="flex items-center gap-1.5 text-xs font-bold text-rose-600 dark:text-rose-400 animate-pulse">
                    <span>🚨</span> {{ $criticalDmi->days_remaining }}d restantes: {{ Str::limit($criticalDmi->title, 16) }}
                </div>
            @elseif ($warningDmi)
                <div class="flex items-center gap-1.5 text-xs font-bold text-amber-600 dark:text-amber-400">
                    <span>⚠️</span> {{ $warningDmi->days_remaining }}d restantes: {{ Str::limit($warningDmi->title, 16) }}
                </div>
            @elseif ($dmiDocuments->count() > 0)
                <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                    <span>🟢</span> {{ $dmiDocuments->count() }} doc(s) en orden
                </div>
            @else
                <div class="flex items-center gap-1.5 text-xs font-medium text-gray-500">
                    <span>🎉</span> Sin alertas abiertas
                </div>
            @endif
        </div>

        <a
            href="?tab=dmi_documents"
            class="text-[11px] font-semibold text-gray-700 dark:text-gray-300 px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 transition-colors"
        >
            Gestionar
        </a>
    </div>
</div>
