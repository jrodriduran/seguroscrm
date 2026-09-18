<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Plan Seleccionado con Éxito!</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-lg w-full bg-white rounded-3xl p-8 border border-slate-200 shadow-xl text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto mb-5">
            ✓
        </div>

        <h1 class="text-2xl font-extrabold text-slate-900 mb-2">¡Felicitaciones!</h1>
        <p class="text-slate-600 text-sm mb-6">
            Su selección ha sido registrada exitosamente en el sistema de su agencia de seguros.
        </p>

        @if ($selectedQuote)
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 mb-6 text-left">
                <div class="text-xs font-bold text-sky-700 uppercase tracking-wider mb-1">
                    Plan Elegido
                </div>
                <div class="text-lg font-extrabold text-slate-900">
                    {{ $selectedQuote->carrier_name }} - {{ $selectedQuote->plan_name }}
                </div>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-200 text-sm">
                    <span class="text-slate-500">Prima Mensual Estimada:</span>
                    <span class="font-extrabold text-slate-900 text-base">
                        ${{ number_format((float) ($selectedQuote->net_premium ?? $selectedQuote->grand_total), 2) }} / mes
                    </span>
                </div>
                <div class="flex items-center justify-between mt-1 text-sm">
                    <span class="text-slate-500">Deducible Anual:</span>
                    <span class="font-semibold text-slate-800">
                        ${{ number_format((float) ($selectedQuote->deductible ?? 0), 2) }}
                    </span>
                </div>
            </div>
        @endif

        <div class="bg-sky-50 border border-sky-200 rounded-xl p-4 text-xs text-sky-900 leading-relaxed mb-6">
            <strong>Próximos Pasos:</strong> Su agente <strong>{{ $agent?->name ?: 'Certificado ACA' }}</strong> verificará los requisitos del Mercado de Seguros y le contactará para formalizar su número de póliza y fecha de inicio de cobertura.
        </div>

        <p class="text-xs text-slate-400">
            Gracias por confiar en Seguros CRM para proteger su salud y la de su familia.
        </p>
    </div>

</body>
</html>
