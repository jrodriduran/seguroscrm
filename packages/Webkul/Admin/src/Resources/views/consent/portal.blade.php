<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Consentimiento de Asistencia ACA / CMS - {{ $consent->client_name }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            touch-action: pan-y;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }
        #signatureCanvas {
            touch-action: none;
            cursor: crosshair;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen pb-12">

    <!-- Top Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-2xl mx-auto px-4 py-3.5 flex items-center justify-between">
            <div class="flex items-center space-x-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-lg shadow-md shadow-blue-500/20">
                    🛡️
                </div>
                <div>
                    <h1 class="text-sm font-bold text-slate-900 leading-tight">Consentimiento Federal ACA</h1>
                    <p class="text-[11px] text-slate-500 font-medium">Normativa CMS 45 CFR § 155.220</p>
                </div>
            </div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Oficial & Seguro
            </div>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 pt-5 space-y-4">

        <!-- Agent & Consumer Card -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm space-y-3">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Agente Certificado</span>
                    <h2 class="text-base font-bold text-slate-900">{{ $consent->agent_name ?: 'Agente Autorizado' }}</h2>
                    <p class="text-xs text-slate-600">NPN: <strong class="text-blue-700 font-mono">{{ $consent->agent_npn ?: '19845210' }}</strong> • {{ $consent->agency_name ?: 'Seguros CRM Agency' }}</p>
                </div>
                <div class="w-11 h-11 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                    👨‍💼
                </div>
            </div>

            <div class="pt-1 flex items-center justify-between text-xs text-slate-600">
                <div>
                    <span class="text-slate-400">Consumidor:</span>
                    <strong class="text-slate-900 block text-sm font-semibold">{{ $consent->client_name }}</strong>
                </div>
                <div class="text-right">
                    <span class="text-slate-400">Fecha de Solicitud:</span>
                    <strong class="text-slate-800 block">{{ now()->format('d/m/Y') }}</strong>
                </div>
            </div>
        </div>

        <!-- CMS Legal Disclosure Accordion / Box -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm space-y-3">
            <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                <span class="text-blue-600">📜</span>
                <h3>Declaración y Autorización del Consumidor</h3>
            </div>
            
            <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200 text-xs text-slate-700 leading-relaxed max-h-56 overflow-y-auto space-y-2.5 font-normal">
                {!! nl2br(e($consent->consent_text)) !!}
            </div>

            <div class="p-3 bg-amber-50/70 border border-amber-200/70 rounded-xl flex items-start gap-2.5 text-[11px] text-amber-900 leading-tight">
                <span class="text-base">🔒</span>
                <span>
                    <strong>Validez Legal Federal:</strong> Conforme a la regla CMS 45 CFR § 155.220, su firma digital, dirección IP, fecha/hora y dispositivo serán registrados como constancia de autorización para la gestión de su póliza médica ante el Mercado de Seguros (Healthcare.gov).
                </span>
            </div>
        </div>

        <!-- Digital Signature Card -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Firma Digital del Titular</h3>
                    <p class="text-xs text-slate-500">Firme con su dedo en la pantalla o con el ratón</p>
                </div>
                <button
                    type="button"
                    id="clearBtn"
                    class="text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100/70 px-3 py-1.5 rounded-lg transition-colors inline-flex items-center gap-1"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Borrar y reintentar
                </button>
            </div>

            <!-- Canvas Container -->
            <div class="relative bg-slate-50 border-2 border-dashed border-slate-300 rounded-xl overflow-hidden touch-none group hover:border-blue-400 transition-colors">
                <canvas id="signatureCanvas" class="w-full h-44 sm:h-52 block bg-white"></canvas>
                <div id="canvasPlaceholder" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center text-slate-400">
                    <svg class="w-8 h-8 mb-1 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    <span class="text-xs font-medium">Dibuje su firma aquí</span>
                </div>
            </div>

            <!-- Confirmation Name Input -->
            <div class="space-y-1.5 pt-1">
                <label for="clientNameInput" class="block text-xs font-semibold text-slate-700">Nombre completo del firmante:</label>
                <input
                    type="text"
                    id="clientNameInput"
                    value="{{ $consent->client_name }}"
                    class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all text-slate-900 font-medium"
                    placeholder="Escriba su nombre completo"
                >
            </div>

            <!-- Acceptance Checkbox -->
            <label class="flex items-start gap-2.5 cursor-pointer select-none pt-1">
                <input type="checkbox" id="acceptCheck" class="w-4 h-4 mt-0.5 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                <span class="text-xs text-slate-600 leading-snug">
                    Confirmo que he leído y acepto los términos de asistencia con el Mercado de Seguros ACA y autorizo al agente indicado a representarme.
                </span>
            </label>

            <!-- Submit Button -->
            <button
                type="button"
                id="submitSignBtn"
                disabled
                class="w-full py-3.5 px-4 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-lg shadow-blue-500/25 transition-all text-sm flex items-center justify-center gap-2"
            >
                <span id="btnIcon">✍️</span>
                <span id="btnText">Confirmar y Enviar Autorización</span>
            </button>
        </div>

        <!-- Audit Note Footer -->
        <div class="text-center text-[11px] text-slate-400 space-y-1 pt-2">
            <p>Conexión encriptada SSL 256-bit • Cumplimiento CMS 45 CFR § 155.220</p>
            <p>IP registrada al firmar para propósitos de validez legal.</p>
        </div>

    </main>

    <!-- Signature Script -->
    <script>
        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        const clearBtn = document.getElementById('clearBtn');
        const submitBtn = document.getElementById('submitSignBtn');
        const placeholder = document.getElementById('canvasPlaceholder');
        const clientNameInput = document.getElementById('clientNameInput');
        const acceptCheck = document.getElementById('acceptCheck');

        let isDrawing = false;
        let hasDrawn = false;

        // Resize canvas to match display size exactly
        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            const dpr = window.devicePixelRatio || 1;
            canvas.width = rect.width * dpr;
            canvas.height = rect.height * dpr;
            ctx.scale(dpr, dpr);
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#0f172a'; // dark slate
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

        function startDrawing(e) {
            e.preventDefault();
            isDrawing = true;
            const pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
            placeholder.style.display = 'none';
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            hasDrawn = true;
            validateForm();
        }

        function stopDrawing() {
            if (!isDrawing) return;
            isDrawing = false;
            validateForm();
        }

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stopDrawing);

        canvas.addEventListener('touchstart', startDrawing, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        window.addEventListener('touchend', stopDrawing);

        clearBtn.addEventListener('click', () => {
            const dpr = window.devicePixelRatio || 1;
            ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
            placeholder.style.display = 'flex';
            hasDrawn = false;
            validateForm();
        });

        acceptCheck.addEventListener('change', validateForm);
        clientNameInput.addEventListener('input', validateForm);

        function validateForm() {
            const nameOk = clientNameInput.value.trim().length > 2;
            const accepted = acceptCheck.checked;
            submitBtn.disabled = !(hasDrawn && nameOk && accepted);
        }

        submitBtn.addEventListener('click', async () => {
            if (!hasDrawn || !acceptCheck.checked) return;

            submitBtn.disabled = true;
            document.getElementById('btnIcon').textContent = '⏳';
            document.getElementById('btnText').textContent = 'Registrando firma y auditoría...';

            const signatureData = canvas.toDataURL('image/png');
            const clientName = clientNameInput.value.trim();

            try {
                const response = await fetch("{{ route('consent.portal.sign', $consent->token) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        signature_data: signatureData,
                        client_name: clientName
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    alert(data.message || 'Error al procesar la firma.');
                    submitBtn.disabled = false;
                    document.getElementById('btnIcon').textContent = '✍️';
                    document.getElementById('btnText').textContent = 'Confirmar y Enviar Autorización';
                }
            } catch (err) {
                console.error(err);
                alert('Ocurrió un error de conexión al guardar su firma.');
                submitBtn.disabled = false;
                document.getElementById('btnIcon').textContent = '✍️';
                document.getElementById('btnText').textContent = 'Confirmar y Enviar Autorización';
            }
        });
    </script>
</body>
</html>
