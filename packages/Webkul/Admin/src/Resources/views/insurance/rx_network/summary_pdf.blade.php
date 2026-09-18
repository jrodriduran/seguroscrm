<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen de Medicinas y Red Médica - {{ $lead->person?->name ?: $lead->title }}</title>
    <style>
        @page {
            margin: 25px 30px;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
        }
        body {
            font-size: 11px;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .header-table {
            border-bottom: 2px solid #0284c7;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .logo-title {
            font-size: 20px;
            font-weight: bold;
            color: #0369a1;
        }
        .subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 15px;
        }
        .meta-label {
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }
        .meta-value {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 15px;
            margin-bottom: 8px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .data-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
            vertical-align: top;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-t1 { background-color: #d1fae5; color: #065f46; }
        .badge-t2 { background-color: #ccfbf1; color: #115e59; }
        .badge-t3 { background-color: #e0f2fe; color: #075985; }
        .badge-t4 { background-color: #fef3c7; color: #92400e; }
        .badge-t5 { background-color: #f3e8ff; color: #6b21a8; }
        .badge-warn { background-color: #fee2e2; color: #991b1b; }
        .badge-in { background-color: #dcfce7; color: #166534; }
        .badge-out { background-color: #fee2e2; color: #991b1b; }
        .footer {
            margin-top: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 65%;">
                <div class="logo-title">FICHA DE FARMACOTERAPIA Y RED MÉDICA</div>
                <div class="subtitle">Verificación de Formulario de Medicamentos y Médicos Preferidos (CMS & ACA Compliant)</div>
            </td>
            <td style="width: 35%; text-align: right;">
                <div style="font-size: 10px; color: #64748b;">Fecha de Emisión: <strong>{{ $generatedAt }}</strong></div>
                <div style="font-size: 10px; color: #64748b;">Agente Responsable: <strong>{{ $lead->user?->name ?: 'Agencia de Seguros' }}</strong></div>
            </td>
        </tr>
    </table>

    <!-- Beneficiary Details -->
    <div class="meta-box">
        <table>
            <tr>
                <td style="width: 25%;">
                    <div class="meta-label">Beneficiario Titular</div>
                    <div class="meta-value">{{ $lead->person?->name ?: $lead->title }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="meta-label">Teléfono de Contacto</div>
                    <div class="meta-value">{{ $lead->person?->contact_numbers?->first()?->number ?: 'N/A' }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="meta-label">Total Medicamentos Registrados</div>
                    <div class="meta-value">{{ $medications->count() }} fármacos</div>
                </td>
                <td style="width: 25%;">
                    <div class="meta-label">Gasto Mensual Estimado en Copagos</div>
                    <div class="meta-value" style="color: #0369a1;">${{ number_format($medications->sum('estimated_copay_30d'), 2) }} / mes</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 1. Formulary / Prescription Medications -->
    <div class="section-title">1. Formulario de Medicamentos Recetados (Prescription Drugs / Part D & ACA)</div>

    @if ($medications->isEmpty())
        <div style="padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; color: #64748b; font-style: italic;">
            No se han registrado medicamentos continuos para este cliente.
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 28%;">Medicamento</th>
                    <th style="width: 14%;">Dosis / Frecuencia</th>
                    <th style="width: 12%;">Cant. (30d)</th>
                    <th style="width: 16%;">Nivel (Tier)</th>
                    <th style="width: 15%;">Restricciones</th>
                    <th style="width: 15%; text-align: right;">Copago Estimado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($medications as $med)
                    <tr>
                        <td>
                            <strong>{{ $med->medication_name }}</strong>
                            @if ($med->notes)
                                <div style="font-size: 8px; color: #64748b;">{{ $med->notes }}</div>
                            @endif
                        </td>
                        <td>{{ $med->dosage }}<br><span style="color: #64748b; font-size: 8px;">{{ $med->frequency }}</span></td>
                        <td>{{ $med->quantity_per_30_days }} un.</td>
                        <td>
                            @php
                                $badgeClass = match(true) {
                                    str_contains($med->drug_tier, 'Tier 1') => 'badge-t1',
                                    str_contains($med->drug_tier, 'Tier 2') => 'badge-t2',
                                    str_contains($med->drug_tier, 'Tier 3') => 'badge-t3',
                                    str_contains($med->drug_tier, 'Tier 4') => 'badge-t4',
                                    str_contains($med->drug_tier, 'Tier 5') => 'badge-t5',
                                    default => 'badge-t1',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $med->drug_tier }}</span>
                        </td>
                        <td>
                            @php $codes = $med->getRestrictionCodes(); @endphp
                            @if (empty($codes))
                                <span style="color: #10b981; font-size: 8px;">Sin restricción</span>
                            @else
                                @foreach ($codes as $c)
                                    <span class="badge badge-warn" title="{{ $c['label'] }}">{{ $c['code'] }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <strong>${{ number_format($med->estimated_copay_30d, 2) }}</strong><br>
                            <span style="font-size: 8px; color: #0284c7;">90d Correo: ${{ number_format($med->estimated_copay_90d_mail, 2) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- 2. Doctors & Providers Network -->
    <div class="section-title" style="margin-top: 20px;">2. Red de Médicos y Especialistas Preferidos (Provider Network Verification)</div>

    @if ($doctors->isEmpty())
        <div style="padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; color: #64748b; font-style: italic;">
            No se han registrado proveedores médicos para este cliente.
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Nombre del Médico</th>
                    <th style="width: 20%;">Especialidad</th>
                    <th style="width: 25%;">Clínica / Hospital / NPI</th>
                    <th style="width: 30%;">Estatus en Red de Carriers</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($doctors as $doc)
                    <tr>
                        <td>
                            <strong>{{ $doc->doctor_name }}</strong>
                            @if ($doc->is_primary_physician)
                                <span class="badge badge-t1">Médico Primario (PCP)</span>
                            @endif
                            @if ($doc->phone)
                                <div style="font-size: 8px; color: #64748b;">Tel: {{ $doc->phone }}</div>
                            @endif
                        </td>
                        <td>{{ $doc->specialty }}</td>
                        <td>
                            {{ $doc->clinic_or_hospital ?: 'Práctica Privada' }}<br>
                            <span style="font-size: 8px; color: #64748b;">NPI: {{ $doc->npi_number ?: 'N/A' }} | {{ $doc->address_city_state ?: '' }}</span>
                        </td>
                        <td>
                            @if (is_array($doc->carrier_network_status))
                                @foreach ($doc->carrier_network_status as $carrier => $status)
                                    @php
                                        $isIn = stripos($status, 'in') !== false;
                                    @endphp
                                    <span class="badge {{ $isIn ? 'badge-in' : 'badge-out' }}">
                                        {{ $carrier }}: {{ $status }}
                                    </span>
                                @endforeach
                            @else
                                <span style="color: #64748b; font-size: 8px;">En verificación</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Compliance Disclaimer -->
    <div style="margin-top: 25px; padding: 10px; background-color: #f8fafc; border-left: 3px solid #0284c7; font-size: 8px; color: #475569;">
        <strong>Nota Regulatoria & Descargo de Responsabilidad:</strong> La disponibilidad en red de médicos y el nivel de cobertura (formularios y copagos) de fármacos están sujetos a cambios periódicos por parte de cada aseguradora según las pautas de CMS y Marketplace. Este resumen constituye una estimación consultiva elaborada en base a la información proporcionada por el asegurado al momento de la cotización.
    </div>

    <!-- Footer -->
    <div class="footer">
        Documento confidencial generado por Krayin Health CRM — Prohibida su reproducción sin autorización.
    </div>

</body>
</html>
