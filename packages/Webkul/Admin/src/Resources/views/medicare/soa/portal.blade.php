<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Medicare Scope of Appointment (SOA) - {{ $soa->beneficiary_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { -webkit-tap-highlight-color: transparent; }
        canvas { touch-action: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

    <!-- Header Banner -->
    <header class="bg-blue-900 text-white py-4 px-6 shadow-md">
        <div class="max-w-2xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🛡️</span>
                <div>
                    <h1 class="text-base font-bold tracking-tight">Formulario de Alcance de Cita (SOA)</h1>
                    <p class="text-xs text-blue-200">Medicare y Medicaid (CMS) • Cumplimiento Federal</p>
                </div>
            </div>
            <span class="text-[10px] bg-blue-800 border border-blue-700 px-2 py-1 rounded text-blue-200 font-mono">
                CMS-48HR
            </span>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-2xl mx-auto p-4 sm:p-6 w-full flex-1">

        @if ($soa->status === 'signed')
            <div class="bg-emerald-50 border border-emerald-300 rounded-2xl p-6 text-center shadow-sm">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl font-bold mb-3">
                    ✓
                </div>
                <h2 class="text-lg font-bold text-emerald-900 mb-1">Este Formulario SOA Ya Ha Sido Firmado</h2>
                <p class="text-sm text-emerald-700 mb-4">
                    Su autorización fue registrada el <strong>{{ $soa->signed_at?->format('d/m/Y h:i A') }}</strong>.
                </p>
                <div class="text-xs text-slate-500 bg-white p-4 rounded-xl border border-slate-200 inline-block text-left">
                    <div><strong>Beneficiario:</strong> {{ $soa->beneficiary_name }}</div>
                    <div><strong>Agente Certificado:</strong> {{ $soa->agent_name }} (NPN: {{ $soa->agent_npn }})</div>
                    <div><strong>Fecha Hábil para Cita:</strong> {{ $soa->appointment_eligible_at?->format('d/m/Y h:i A') }}</div>
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-7 space-y-6">

                <!-- Instructions Alert -->
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 space-y-2">
                    <div class="font-bold flex items-center gap-1.5 text-amber-800 text-sm">
                        <span>ℹ️</span> ¿Por qué solicitamos este formulario?
                    </div>
                    <p>
                        Los Centros de Servicios de Medicare y Medicaid (CMS) requieren que los beneficiarios acuerden con su agente qué tipos de planes desean evaluar antes de su reunión personal. <strong>Completar este formulario no le obliga a inscribirse en ningún plan ni afecta su cobertura actual de Medicare.</strong>
                    </p>
                </div>

                <!-- Beneficiary & Agent Identity Box -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-slate-400 block uppercase tracking-wider font-semibold">Beneficiario / Solicitante:</span>
                        <strong class="text-slate-800 text-sm block">{{ $soa->beneficiary_name }}</strong>
                        <span class="text-slate-500">{{ $soa->beneficiary_phone ?: 'Teléfono verificado' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block uppercase tracking-wider font-semibold">Agente Certificado Medicare:</span>
                        <strong class="text-slate-800 text-sm block">{{ $soa->agent_name }}</strong>
                        <span class="text-slate-500">NPN: {{ $soa->agent_npn }} • {{ $soa->agency_name ?: 'Medicare Agency' }}</span>
                    </div>
                </div>

                <!-- Scope of Products Selection (Checkboxes) -->
                <div>
                    <h3 class="text-sm font-bold text-slate-900 mb-2">
                        Marque los tipos de productos que autoriza discutir durante su cita:
                    </h3>
                    <div class="space-y-3 text-xs" id="products-container">
                        <label class="flex items-start gap-3 p-3 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200 cursor-pointer transition">
                            <input type="checkbox" id="chk_ma" checked class="mt-0.5 rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                            <div>
                                <span class="font-bold text-slate-800 block text-sm">Planes Medicare Advantage (Parte C)</span>
                                <span class="text-slate-500">Planes HMO y PPO de salud que combinan Parte A (Hospital), Parte B (Médico) y beneficios adicionales como dental y visión.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200 cursor-pointer transition">
                            <input type="checkbox" id="chk_pdp" checked class="mt-0.5 rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                            <div>
                                <span class="font-bold text-slate-800 block text-sm">Planes de Medicamentos Recetados (Parte D)</span>
                                <span class="text-slate-500">Planes independientes para la cobertura y descuento de medicamentos recetados en farmacia.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200 cursor-pointer transition">
                            <input type="checkbox" id="chk_medigap" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                            <div>
                                <span class="font-bold text-slate-800 block text-sm">Pólizas Suplementarias de Medicare (Medigap)</span>
                                <span class="text-slate-500">Pólizas estandarizadas que ayudan a pagar deducibles, copagos y coseguros del Medicare Original.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200 cursor-pointer transition">
                            <input type="checkbox" id="chk_dental" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                            <div>
                                <span class="font-bold text-slate-800 block text-sm">Cobertura Suplementaria Dental, Visión y Audición</span>
                                <span class="text-slate-500">Pólizas independientes para servicios dentales mayores, lentes y audífonos.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200 cursor-pointer transition">
                            <input type="checkbox" id="chk_hospital" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                            <div>
                                <span class="font-bold text-slate-800 block text-sm">Indemnización Hospitalaria / Gastos Finales</span>
                                <span class="text-slate-500">Beneficios complementarios en efectivo para hospitalizaciones imprevistas o gastos funerarios.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- CMS TPMO Disclaimer -->
                <div class="p-3 bg-slate-100 rounded-xl text-[11px] text-slate-600 leading-relaxed border border-slate-200">
                    <strong>Descargo de Responsabilidad Oficial de CMS:</strong> {{ \Webkul\Lead\Models\LeadMedicareSoa::getTpmoDisclaimer() }}
                </div>

                <!-- Touchscreen Signature Canvas -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <span>✍️</span> Dibuje su firma aquí:
                        </label>
                        <button
                            type="button"
                            id="btn-clear"
                            class="text-xs text-blue-600 hover:text-blue-800 underline font-medium"
                        >
                            Borrar y Reintentar
                        </button>
                    </div>

                    <div class="border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 overflow-hidden relative">
                        <canvas id="signature-pad" width="600" height="180" class="w-full h-44 bg-white block"></canvas>
                        <div id="signature-placeholder" class="absolute inset-0 flex items-center justify-center pointer-events-none text-slate-400 text-xs font-medium">
                            Use su dedo o mouse para firmar aquí
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="button"
                    id="btn-submit"
                    class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold rounded-xl shadow-md transition-all text-sm flex items-center justify-center gap-2"
                >
                    <span>✓</span> Firmar Scope of Appointment (SOA)
                </button>
            </div>
        @endif

    </main>

    <!-- Footer -->
    <footer class="py-4 text-center text-xs text-slate-400">
        Cumplimiento Federal Medicare CMS • Token: {{ substr($soa->token, 0, 12) }}...
    </footer>

    <!-- Signature Pad Logic -->
    <script>
        const canvas = document.getElementById('signature-pad');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            const placeholder = document.getElementById('signature-placeholder');
            const btnClear = document.getElementById('btn-clear');
            const btnSubmit = document.getElementById('btn-submit');

            let isDrawing = false;
            let hasDrawn = false;

            // Resize canvas resolution
            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                ctx.scale(ratio, ratio);
                ctx.lineWidth = 2.5;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#0f172a';
            }

            window.addEventListener('resize', resizeCanvas);
            resizeCanvas();

            function getPos(e) {
                const rect = canvas.getBoundingClientRect();
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                return {
                    x: clientX - rect.left,
                    y: clientY - rect.top
                };
            }

            function startDraw(e) {
                isDrawing = true;
                placeholder.style.display = 'none';
                const pos = getPos(e);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
                if (e.cancelable) e.preventDefault();
            }

            function draw(e) {
                if (!isDrawing) return;
                hasDrawn = true;
                const pos = getPos(e);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
                if (e.cancelable) e.preventDefault();
            }

            function stopDraw() {
                isDrawing = false;
            }

            canvas.addEventListener('mousedown', startDraw);
            canvas.addEventListener('mousemove', draw);
            window.addEventListener('mouseup', stopDraw);

            canvas.addEventListener('touchstart', startDraw, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            window.addEventListener('touchend', stopDraw);

            btnClear.addEventListener('click', () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasDrawn = false;
                placeholder.style.display = 'flex';
            });

            btnSubmit.addEventListener('click', () => {
                if (!hasDrawn) {
                    alert('Por favor dibuje su firma en el recuadro antes de continuar.');
                    return;
                }

                btnSubmit.disabled = true;
                btnSubmit.innerText = 'Registrando firma y verificando normas CMS...';

                const signatureData = canvas.toDataURL('image/png');

                fetch("{{ route('medicare.soa.sign', $soa->token) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        signature: signatureData,
                        discuss_medicare_advantage: document.getElementById('chk_ma')?.checked,
                        discuss_prescription_drug: document.getElementById('chk_pdp')?.checked,
                        discuss_medigap: document.getElementById('chk_medigap')?.checked,
                        discuss_dental_vision: document.getElementById('chk_dental')?.checked,
                        discuss_hospital_indemnity: document.getElementById('chk_hospital')?.checked
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect_url;
                    } else {
                        alert(data.message || 'Error al guardar la firma.');
                        btnSubmit.disabled = false;
                        btnSubmit.innerText = 'Firmar Scope of Appointment (SOA)';
                    }
                })
                .catch(err => {
                    alert('Error en la comunicación con el servidor.');
                    btnSubmit.disabled = false;
                    btnSubmit.innerText = 'Firmar Scope of Appointment (SOA)';
                });
            });
        }
    </script>
</body>
</html>
