<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOA Firmado Exitosamente - Medicare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

    <header class="bg-blue-900 text-white py-4 px-6 shadow-md text-center">
        <h1 class="text-base font-bold">Scope of Appointment (SOA) - CMS</h1>
        <p class="text-xs text-blue-200">Constancia de Consentimiento Previo a la Cita</p>
    </header>

    <main class="max-w-xl mx-auto p-6 w-full flex-1 flex items-center justify-center">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8 text-center space-y-6 w-full">
            <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-4xl font-bold shadow-inner">
                ✓
            </div>

            <div class="space-y-2">
                <h2 class="text-xl font-bold text-slate-900">¡Muchas Gracias! Formulario Firmado</h2>
                <p class="text-sm text-slate-600">
                    Su autorización previa ha sido registrada y archivada conforme a los requerimientos oficiales de Medicare (CMS).
                </p>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs text-left space-y-2">
                <div class="flex justify-between border-b border-slate-200 pb-1.5">
                    <span class="text-slate-400">Beneficiario:</span>
                    <strong class="text-slate-800">{{ $soa->beneficiary_name }}</strong>
                </div>
                <div class="flex justify-between border-b border-slate-200 pb-1.5">
                    <span class="text-slate-400">Agente Certificado:</span>
                    <span class="text-slate-800 font-semibold">{{ $soa->agent_name }} (NPN: {{ $soa->agent_npn }})</span>
                </div>
                <div class="flex justify-between border-b border-slate-200 pb-1.5">
                    <span class="text-slate-400">Fecha y Hora de Firma:</span>
                    <span class="text-slate-800 font-semibold">{{ $soa->signed_at?->format('d/m/Y h:i:s A') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Período de Cumplimiento (48h):</span>
                    <span class="text-emerald-700 font-bold">Cita habilitada a partir del {{ $soa->appointment_eligible_at?->format('d/m/Y h:i A') }}</span>
                </div>
            </div>

            <p class="text-xs text-slate-500 leading-relaxed">
                Su agente se pondrá en contacto con usted para confirmar la fecha y hora de su cita para evaluar las opciones autorizadas. Puede cerrar esta ventana con total seguridad.
            </p>
        </div>
    </main>

    <footer class="py-4 text-center text-xs text-slate-400">
        Cumplimiento Oficial CMS Medicare • Seguro y Confidencial
    </footer>

</body>
</html>
