<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tarjeta Médica Digital & Portal del Asegurado | {{ $policy->policy_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-gradient-blue { background: linear-gradient(135deg, #0369a1 0%, #0c4a6e 100%); }
        .card-gradient-emerald { background: linear-gradient(135deg, #047857 0%, #064e3b 100%); }
        .card-gradient-dark { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen pb-12">

    <!-- Top Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-3xl mx-auto px-4 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-2xl">🛡️</span>
                <div>
                    <div class="font-extrabold text-sm tracking-tight text-slate-900">PORTAL DEL ASEGURADO</div>
                    <div class="text-[10px] text-slate-500">Credencial Médica Oficial</div>
                </div>
            </div>

            <a 
                href="{{ route('insured.portal.download_card', $token) }}" 
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-700 hover:bg-sky-800 text-white rounded-xl text-xs font-bold transition-colors shadow-sm"
            >
                <span>📥</span> Descargar Tarjeta PDF
            </a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 pt-6 space-y-6">

        @php
            $carrierName = strtoupper($policy->carrier_name ?: 'SEGURO DE SALUD');
            $clientName = $policy->person?->name ?: ($policy->lead?->person?->name ?: 'Asegurado Principal');
            $tier = strtoupper($policy->metal_tier ?: 'SILVER');
            $net = $policy->quote ? (float)$policy->quote->net_premium : (float)$policy->net_premium;
            $copayPcp = $policy->quote ? (float)$policy->quote->copay_primary_care : 0;
            $copaySpec = $policy->quote ? (float)$policy->quote->copay_specialist : 0;
            $copayRx = $policy->quote ? (float)$policy->quote->copay_generic_drugs : 0;
            $deductible = $policy->quote ? (float)$policy->quote->deductible : 0;
        @endphp

        <!-- DIGITAL INSURANCE CARD (WALLET STYLE) -->
        <div class="card-gradient-blue rounded-3xl p-6 sm:p-7 text-white shadow-xl relative overflow-hidden border border-sky-400/20">
            <div class="absolute -right-12 -bottom-12 w-48 h-48 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Card Header -->
            <div class="flex items-start justify-between mb-6">
                <div>
                    <span class="inline-block bg-white/20 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider mb-1.5">
                        {{ $tier }} • {{ strtoupper($policy->network_type ?: 'HMO') }}
                    </span>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white">
                        {{ $carrierName }}
                    </h2>
                    <p class="text-sky-200 text-xs mt-0.5 font-medium">
                        {{ $policy->plan_name ?: 'Plan de Cobertura Médica' }}
                    </p>
                </div>

                <div class="text-right">
                    <span class="inline-block w-8 h-8 rounded-full bg-emerald-500/30 border border-emerald-400/50 text-emerald-300 text-center leading-7 font-bold text-xs">
                        ✓
                    </span>
                    <div class="text-[10px] font-bold text-emerald-300 mt-1 uppercase">Vigente</div>
                </div>
            </div>

            <!-- Member & Group Data Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 py-4 border-t border-b border-sky-400/20 text-xs">
                <div>
                    <div class="text-sky-300/80 text-[10px] uppercase font-bold tracking-wider">Member ID</div>
                    <div class="font-extrabold text-white text-sm sm:text-base tracking-wide mt-0.5">
                        {{ $policy->member_id ?: 'MBR-9842103' }}
                    </div>
                </div>

                <div>
                    <div class="text-sky-300/80 text-[10px] uppercase font-bold tracking-wider">N° de Póliza</div>
                    <div class="font-extrabold text-white text-sm sm:text-base tracking-wide mt-0.5">
                        {{ $policy->policy_number }}
                    </div>
                </div>

                <div>
                    <div class="text-sky-300/80 text-[10px] uppercase font-bold tracking-wider">Group Number</div>
                    <div class="font-extrabold text-white text-sm sm:text-base tracking-wide mt-0.5">
                        {{ $policy->group_number ?: 'GRP-FLB-8092' }}
                    </div>
                </div>
            </div>

            <!-- Card Bottom: Insured Name & Copays -->
            <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <div class="text-sky-300/80 text-[10px] uppercase font-bold tracking-wider">Titular Asegurado</div>
                    <div class="text-base sm:text-lg font-extrabold text-white tracking-tight mt-0.5">
                        {{ $clientName }}
                    </div>
                    <div class="text-[11px] text-sky-200 mt-0.5">
                        Efectividad: {{ $policy->effective_date ? $policy->effective_date->format('d/m/Y') : '01/01/2026' }}
                    </div>
                </div>

                <!-- Copays Pill Box -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/15 text-[11px] flex gap-3 text-center">
                    <div>
                        <div class="text-sky-200 text-[9px] font-bold uppercase">Médico (PCP)</div>
                        <div class="font-extrabold text-white text-xs">${{ number_format($copayPcp, 2) }}</div>
                    </div>
                    <div class="border-l border-white/20 pl-3">
                        <div class="text-sky-200 text-[9px] font-bold uppercase">Especialista</div>
                        <div class="font-extrabold text-white text-xs">${{ number_format($copaySpec, 2) }}</div>
                    </div>
                    <div class="border-l border-white/20 pl-3">
                        <div class="text-sky-200 text-[9px] font-bold uppercase">Farmacia Rx</div>
                        <div class="font-extrabold text-white text-xs">${{ number_format($copayRx, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK ASSISTANCE CALL BUTTONS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <a 
                href="tel:{{ $support['phone'] }}" 
                class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5 hover:border-sky-500 transition-colors"
            >
                <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold">
                    📞
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-900">Atención al Miembro {{ $policy->carrier_name }}</div>
                    <div class="text-xs text-sky-600 font-bold mt-0.5">{{ $support['phone'] }}</div>
                    <div class="text-[10px] text-slate-400">Consultas de red, citas y reclamos</div>
                </div>
            </a>

            <a 
                href="tel:{{ $support['nurse_line'] }}" 
                class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3.5 hover:border-rose-500 transition-colors"
            >
                <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold">
                    🩺
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-900">Línea de Enfermería 24/7</div>
                    <div class="text-xs text-rose-600 font-bold mt-0.5">{{ $support['nurse_line'] }}</div>
                    <div class="text-[10px] text-slate-400">Orientación médica inmediata sin costo</div>
                </div>
            </a>
        </div>

        <!-- COVERED FAMILY MEMBERS -->
        @if ($coveredMembers->isNotEmpty())
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
                <h3 class="font-bold text-sm text-slate-900 mb-3 flex items-center gap-2">
                    <span>👨‍👩‍👧‍👦</span> Miembros Cubiertos en la Póliza ({{ $coveredMembers->count() + 1 }})
                </h3>

                <div class="divide-y divide-slate-100 text-xs">
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-900">{{ $clientName }}</span>
                            <span class="ml-2 text-[10px] px-2 py-0.5 bg-slate-100 font-bold rounded-full text-slate-600">Titular</span>
                        </div>
                        <span class="text-emerald-600 font-bold">✓ Cobertura Activa</span>
                    </div>

                    @foreach ($coveredMembers as $dep)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-800">{{ $dep->name }}</span>
                                <span class="ml-2 text-[10px] text-slate-400">({{ ucfirst($dep->relationship) }})</span>
                            </div>
                            <span class="text-emerald-600 font-bold">✓ Cobertura Activa</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- AGENT CONTACT CARD -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-extrabold text-base">
                    👤
                </div>
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Su Agente de Seguros Certificado</div>
                    <div class="font-extrabold text-slate-900 text-sm mt-0.5">{{ $agent?->name ?: 'Agente Principal' }}</div>
                    <div class="text-xs text-slate-500">NPN: 19845210 | Licenciado en Salud & Vida</div>
                </div>
            </div>

            @if ($agent && $agent->email)
                <a 
                    href="mailto:{{ $agent->email }}" 
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors"
                >
                    ✉️ Enviar Mensaje
                </a>
            @endif
        </div>

        <!-- SELF-SERVICE DOCUMENT UPLOAD (DMI / PROOF OF INCOME) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-lg">📤</span>
                <h3 class="font-bold text-sm text-slate-900">Subir Documento al Mercado (Healthcare.gov)</h3>
            </div>
            <p class="text-xs text-slate-500 mb-4">
                Si el Mercado le solicitó comprobante de ingresos, estatus migratorio o identidad, puede subirlo directamente aquí para que su agente lo valide.
            </p>

            <form id="docUploadForm" onsubmit="handleDocUpload(event)" class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tipo de Documento</label>
                    <select id="docType" class="w-full text-xs border border-slate-300 rounded-lg p-2.5 bg-slate-50 focus:ring-2 focus:ring-sky-500">
                        <option value="income">Comprobante de Ingresos (Talón de cheque / W2 / 1040)</option>
                        <option value="immigration">Estatus Migratorio (Permiso I-766 / Green Card / I-797)</option>
                        <option value="citizenship_identity">Identidad / Ciudadanía (Licencia / Pasaporte / Social)</option>
                        <option value="incarceration">Fin de Encarcelamiento / Otro</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Seleccionar Archivo (Foto o PDF)</label>
                    <input type="file" id="docFile" required accept="image/*,.pdf" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 cursor-pointer">
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit" id="btnUploadDoc" class="px-5 py-2.5 bg-sky-700 hover:bg-sky-800 text-white rounded-xl text-xs font-bold transition-colors shadow-sm">
                        Enviar Documento
                    </button>
                </div>
            </form>
        </div>

    </main>

    <script>
        function handleDocUpload(e) {
            e.preventDefault();
            const btn = document.getElementById('btnUploadDoc');
            const fileInput = document.getElementById('docFile');
            const docType = document.getElementById('docType').value;

            if (! fileInput.files.length) {
                alert('Seleccione un archivo');
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Subiendo archivo...';

            const formData = new FormData();
            formData.append('document', fileInput.files[0]);
            formData.append('doc_type', docType);

            fetch("{{ route('insured.portal.upload_doc', $token) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerText = 'Enviar Documento';
                if (data.success) {
                    alert(data.message);
                    document.getElementById('docUploadForm').reset();
                } else {
                    alert(data.message || 'Error al subir documento');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerText = 'Enviar Documento';
                alert('Error de conexión al cargar archivo.');
            });
        }
    </script>
</body>
</html>
