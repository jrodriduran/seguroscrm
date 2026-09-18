<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Comparativa de Planes de Salud | {{ $lead->person?->name ?: $lead->title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">

    <!-- Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🛡️</span>
                    <span class="font-extrabold text-lg tracking-tight text-sky-800">SEGUROS CRM</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Portal de Comparativa de Salud ACA 2026</p>
            </div>

            <div class="text-right">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Su Agente Asignado</div>
                <div class="text-sm font-bold text-slate-900">{{ $agent?->name ?: 'Agente Certificado' }}</div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-6xl mx-auto px-4 py-8">
        
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-sky-800 to-blue-900 rounded-2xl p-6 sm:p-8 text-white mb-8 shadow-lg">
            <span class="inline-block bg-sky-600/50 text-sky-200 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-3">
                Propuesta Personalizada
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Hola, {{ $lead->person?->name ?: 'Estimado Cliente' }}
            </h1>
            <p class="text-sky-100 text-sm sm:text-base mt-2 max-w-2xl leading-relaxed">
                Su agente ha seleccionado estas opciones de cobertura médica del Mercado Oficial de Seguros (ACA / Obamacare). Compare los beneficios y seleccione su plan favorito.
            </p>

            @php
                $firstQuote = $quotes->first();
                $subsidy = (float) ($firstQuote?->aptc_subsidy ?? 0);
            @endphp

            @if ($subsidy > 0)
                <div class="mt-5 bg-emerald-500/20 border border-emerald-400/40 rounded-xl p-4 flex items-center gap-3">
                    <span class="text-3xl">💰</span>
                    <div>
                        <div class="text-sm font-bold text-emerald-200">
                            Subsidio Federal Estimado: ${{ number_format($subsidy, 2) }} / mes
                        </div>
                        <div class="text-xs text-emerald-100 mt-0.5">
                            Este crédito fiscal del gobierno ya fue aplicado directamente para reducir su pago mensual.
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if ($proposal->status === 'accepted' && $proposal->selectedQuote)
            <div class="bg-emerald-50 border border-emerald-300 rounded-xl p-5 mb-8 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-lg">
                    ✓
                </div>
                <div>
                    <h3 class="font-bold text-emerald-900">¡Ya ha seleccionado su plan preferido!</h3>
                    <p class="text-sm text-emerald-700">
                        Seleccionó: <strong>{{ $proposal->selectedQuote->plan_name }} ({{ $proposal->selectedQuote->carrier_name }})</strong>. Su agente se encuentra tramitando su solicitud.
                    </p>
                </div>
            </div>
        @endif

        <!-- Comparison Grid -->
        <div class="grid grid-cols-1 md:grid-cols-{{ count($quotes) > 2 ? '3' : '2' }} gap-6 mb-8">
            @foreach ($quotes as $index => $quote)
                @php
                    $isChosen = $proposal->selected_quote_id === $quote->id;
                    $tier = strtolower($quote->metal_tier ?: 'silver');
                    $badgeStyle = match($tier) {
                        'bronze' => 'bg-amber-100 text-amber-800 border-amber-300',
                        'gold' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                        'platinum' => 'bg-purple-100 text-purple-800 border-purple-300',
                        default => 'bg-slate-100 text-slate-800 border-slate-300',
                    };
                @endphp

                <div class="bg-white rounded-2xl border-2 {{ $isChosen ? 'border-emerald-500 ring-2 ring-emerald-400/30' : 'border-slate-200' }} overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                    
                    <div>
                        <!-- Card Header -->
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border {{ $badgeStyle }}">
                                    {{ strtoupper($tier) }}
                                </span>
                                <span class="text-xs font-semibold text-slate-500">
                                    Red {{ strtoupper($quote->network_type ?: 'HMO') }}
                                </span>
                            </div>

                            <div class="text-xs font-bold text-sky-700 uppercase tracking-wider">
                                {{ $quote->carrier_name ?: 'Aseguradora' }}
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 mt-1 leading-snug">
                                {{ $quote->plan_name ?: ($quote->subject ?: 'Plan de Salud') }}
                            </h3>

                            <!-- Price Hero -->
                            <div class="mt-5 pt-4 border-t border-slate-200/60 flex items-baseline justify-between">
                                <div>
                                    <span class="text-3xl font-extrabold text-slate-900">
                                        ${{ number_format((float) ($quote->net_premium ?? $quote->grand_total), 2) }}
                                    </span>
                                    <span class="text-xs text-slate-500 font-medium">/ mes</span>
                                </div>

                                @if ((float) $quote->gross_premium > (float) $quote->net_premium)
                                    <div class="text-right">
                                        <div class="text-xs text-slate-400 line-through">
                                            ${{ number_format((float) $quote->gross_premium, 2) }}
                                        </div>
                                        <div class="text-[10px] font-semibold text-emerald-600">
                                            Ahorro del subsidio
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Benefit Details -->
                        <div class="p-6 space-y-3.5 text-sm">
                            <div class="flex items-center justify-between py-1 border-b border-slate-100">
                                <span class="text-slate-600 font-medium">Deducible Médico</span>
                                <span class="font-bold text-slate-900">${{ number_format((float) ($quote->deductible ?? 0), 2) }}</span>
                            </div>

                            <div class="flex items-center justify-between py-1 border-b border-slate-100">
                                <span class="text-slate-600 font-medium">Gasto Máximo Anual</span>
                                <span class="font-bold text-slate-900">${{ number_format((float) ($quote->out_of_pocket_max ?? 0), 2) }}</span>
                            </div>

                            <div class="flex items-center justify-between py-1 border-b border-slate-100">
                                <span class="text-slate-600 font-medium">Médico Primario</span>
                                <span class="font-bold text-sky-700">${{ number_format((float) ($quote->copay_primary_care ?? 0), 2) }} Copago</span>
                            </div>

                            <div class="flex items-center justify-between py-1 border-b border-slate-100">
                                <span class="text-slate-600 font-medium">Especialista</span>
                                <span class="font-bold text-slate-900">${{ number_format((float) ($quote->copay_specialist ?? 0), 2) }} Copago</span>
                            </div>

                            <div class="flex items-center justify-between py-1">
                                <span class="text-slate-600 font-medium">Medicamentos Genéricos</span>
                                <span class="font-bold text-emerald-700">${{ number_format((float) ($quote->copay_generic_drugs ?? 0), 2) }} Copago</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Action Footer -->
                    <div class="p-6 bg-slate-50 border-t border-slate-100">
                        @if ($isChosen)
                            <div class="w-full py-3 bg-emerald-600 text-white text-center font-bold rounded-xl shadow-sm flex items-center justify-center gap-2">
                                <span>✓</span> Plan Seleccionado
                            </div>
                        @else
                            <button 
                                type="button" 
                                onclick="openSelectModal({{ $quote->id }}, '{{ addslashes($quote->plan_name) }}', '{{ addslashes($quote->carrier_name) }}', '{{ number_format((float)($quote->net_premium ?? $quote->grand_total), 2) }}')"
                                class="w-full py-3 bg-sky-700 hover:bg-sky-800 text-white font-bold rounded-xl shadow-sm transition-colors text-center text-sm"
                            >
                                Elegir este Plan
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Help Notice -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 text-center max-w-xl mx-auto text-xs text-slate-500">
            ¿Tiene dudas antes de elegir? Puede comunicarse directamente con su agente <strong>{{ $agent?->name }}</strong> llamando al teléfono registrado o por WhatsApp.
        </div>
    </main>

    <!-- Selection Confirmation Modal -->
    <div id="selectionModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl animate-fade-in">
            <h3 class="text-lg font-bold text-slate-900 mb-2">Confirmar Selección de Cobertura</h3>
            <p class="text-sm text-slate-600 mb-4" id="modalPlanDesc">
                ¿Desea formalizar su inscripción en este plan de salud?
            </p>

            <div class="mb-4">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    Nota o Comentario para su Agente (Opcional)
                </label>
                <textarea 
                    id="clientNotes" 
                    rows="3" 
                    class="w-full text-sm border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-sky-500 focus:outline-none"
                    placeholder="Ej. Prefiero cita por la tarde, tengo una consulta sobre mi médico..."
                ></textarea>
            </div>

            <div class="flex gap-3">
                <button 
                    type="button" 
                    onclick="closeSelectModal()"
                    class="flex-1 py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm"
                >
                    Cancelar
                </button>
                <button 
                    type="button" 
                    id="btnConfirmChoice"
                    onclick="submitChoice()"
                    class="flex-1 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm transition-colors"
                >
                    Confirmar Plan
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentQuoteId = null;

        function openSelectModal(quoteId, planName, carrier, price) {
            currentQuoteId = quoteId;
            document.getElementById('modalPlanDesc').innerHTML = `Ha elegido: <strong>${carrier} - ${planName}</strong> por un costo mensual de <strong>$${price} / mes</strong>.<br><br>Al confirmar, su agente recibirá una notificación para formalizar la emisión.`;
            document.getElementById('selectionModal').classList.remove('hidden');
        }

        function closeSelectModal() {
            document.getElementById('selectionModal').classList.add('hidden');
        }

        function submitChoice() {
            if (! currentQuoteId) return;

            const btn = document.getElementById('btnConfirmChoice');
            btn.disabled = true;
            btn.innerText = 'Guardando...';

            const notes = document.getElementById('clientNotes').value;
            const token = "{{ $proposal->token }}";

            fetch("{{ route('proposal.portal.select', $proposal->token) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    quote_id: currentQuoteId,
                    notes: notes
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    alert(data.message || 'Ocurrió un error');
                    btn.disabled = false;
                    btn.innerText = 'Confirmar Plan';
                }
            })
            .catch(err => {
                alert('Error de conexión. Intente nuevamente.');
                btn.disabled = false;
                btn.innerText = 'Confirmar Plan';
            });
        }
    </script>
</body>
</html>
