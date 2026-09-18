<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consentimiento Certificado - {{ $consent->client_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen antialiased flex flex-col justify-between p-4 sm:p-6">

    <div class="max-w-xl mx-auto w-full pt-4 space-y-6">

        <!-- Top Success Badge -->
        <div class="text-center space-y-3">
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl shadow-sm border border-emerald-200/80">
                ✓
            </div>
            <h1 class="text-2xl font-bold text-slate-900">¡Autorización Firmada Exitosamente!</h1>
            <p class="text-sm text-slate-600 max-w-md mx-auto">
                Su consentimiento ha sido registrado legalmente bajo las directrices federales de los Centros de Servicios de Medicare y Medicaid (CMS).
            </p>
        </div>

        <!-- Certificate Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-md p-5 sm:p-6 space-y-5">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <span class="text-[11px] font-semibold tracking-wider text-slate-400 uppercase">Certificado de Cumplimiento</span>
                    <h2 class="text-base font-bold text-slate-900">CMS 45 CFR § 155.220</h2>
                </div>
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-50 text-emerald-700 font-semibold text-xs rounded-full border border-emerald-200">
                    🟢 Activo y Válido
                </span>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-slate-400 block mb-0.5">Consumidor:</span>
                    <strong class="text-slate-900 text-sm font-semibold">{{ $consent->client_name }}</strong>
                </div>

                <div>
                    <span class="text-slate-400 block mb-0.5">Agente Certificado:</span>
                    <strong class="text-slate-900 text-sm font-semibold">{{ $consent->agent_name }}</strong>
                    <span class="text-[11px] text-slate-500 block">NPN: {{ $consent->agent_npn }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block mb-0.5">Fecha y Hora de Firma:</span>
                    <strong class="text-slate-800 font-medium">{{ $consent->signed_at ? $consent->signed_at->format('d/m/Y h:i:s A') : 'N/A' }}</strong>
                </div>

                <div>
                    <span class="text-slate-400 block mb-0.5">Dirección IP Registrada:</span>
                    <strong class="text-slate-800 font-mono">{{ $consent->ip_address ?: '127.0.0.1' }}</strong>
                </div>
            </div>

            <!-- Signature Display -->
            @if ($consent->signature_data)
                <div class="space-y-1.5 pt-2 border-t border-slate-100">
                    <span class="text-[11px] text-slate-400 font-semibold uppercase">Firma Electrónica Registrada:</span>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex items-center justify-center">
                        <img src="{{ $consent->signature_data }}" alt="Firma del cliente" class="h-24 max-w-full object-contain">
                    </div>
                </div>
            @endif

            <!-- Security Hash / Token -->
            <div class="p-3 bg-slate-50 rounded-xl text-[11px] text-slate-500 font-mono break-all border border-slate-200">
                <span class="font-semibold text-slate-700">Identificador Único de Auditoría:</span><br>
                {{ $consent->token }}
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3">
            <button
                onclick="window.print()"
                class="flex-1 py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm rounded-xl transition-colors shadow-sm flex items-center justify-center gap-2"
            >
                🖨️ Imprimir / Guardar en PDF
            </button>
        </div>

    </div>

    <!-- Footer -->
    <footer class="text-center text-xs text-slate-400 py-6">
        <p>© {{ date('Y') }} {{ $consent->agency_name ?: 'Seguros CRM' }}. Todos los derechos reservados.</p>
        <p class="text-[11px] mt-1">Este documento electrónico cumple con el Acta ESIGN (15 U.S.C. § 7001) y la regulación CMS.</p>
    </footer>

</body>
</html>
