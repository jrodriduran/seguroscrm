<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tarjeta Médica de Seguro</title>
    <style>
        @page {
            margin: 25px;
            size: letter portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .card-container {
            width: 100%;
            margin-bottom: 25px;
        }
        .id-card {
            width: 340px;
            height: 200px;
            border: 2px solid #0284c7;
            border-radius: 12px;
            padding: 14px;
            box-sizing: border-box;
            background-color: #f0f9ff;
        }
        .card-table {
            width: 100%;
        }
        .card-header-table {
            width: 100%;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .carrier-title {
            font-size: 15px;
            font-weight: bold;
            color: #0369a1;
            text-transform: uppercase;
        }
        .plan-title {
            font-size: 9px;
            color: #475569;
        }
        .meta-col {
            font-size: 9px;
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
        }
        .meta-value {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }
        .copay-box {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px;
            margin-top: 8px;
            text-align: center;
        }
        .instruction-note {
            font-size: 10px;
            color: #64748b;
            margin-top: 20px;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <h2 style="color: #0f172a; font-size: 16px; margin-bottom: 4px;">Credencial Médica de Cobertura</h2>
    <p style="font-size: 11px; color: #64748b; margin-top: 0; margin-bottom: 20px;">
        Esta tarjeta sirve como comprobante provisional de su seguro médico ante consultorios, hospitales y farmacias.
    </p>

    <table class="card-container">
        <tr>
            <!-- FRONT OF CARD -->
            <td style="width: 50%; vertical-align: top; padding-right: 12px;">
                <div style="font-size: 10px; font-weight: bold; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">Frente de la Tarjeta</div>
                <div class="id-card">
                    <table class="card-header-table">
                        <tr>
                            <td>
                                <div class="carrier-title">{{ $policy->carrier_name }}</div>
                                <div class="plan-title">{{ $policy->plan_name }} ({{ strtoupper($policy->network_type ?: 'HMO') }})</div>
                            </td>
                            <td style="text-align: right; vertical-align: top;">
                                <span style="font-size: 9px; font-weight: bold; background-color: #0284c7; color: white; padding: 2px 6px; border-radius: 4px;">
                                    {{ strtoupper($policy->metal_tier ?: 'SILVER') }}
                                </span>
                            </td>
                        </tr>
                    </table>

                    <table style="width: 100%; margin-top: 5px;">
                        <tr>
                            <td style="width: 50%;">
                                <div class="meta-col">Titular</div>
                                <div class="meta-value">{{ $policy->person?->name ?: ($policy->lead?->person?->name ?: 'Asegurado') }}</div>
                            </td>
                            <td style="width: 50%;">
                                <div class="meta-col">Member ID</div>
                                <div class="meta-value">{{ $policy->member_id ?: 'MBR-9842103' }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-top: 6px;">
                                <div class="meta-col">Póliza / Caso</div>
                                <div class="meta-value">{{ $policy->policy_number }}</div>
                            </td>
                            <td style="padding-top: 6px;">
                                <div class="meta-col">Group Number</div>
                                <div class="meta-value">{{ $policy->group_number ?: 'GRP-8092' }}</div>
                            </td>
                        </tr>
                    </table>

                    <div style="margin-top: 10px; font-size: 9px; color: #475569;">
                        Efectividad: {{ $policy->effective_date ? $policy->effective_date->format('d/m/Y') : '01/01/2026' }} | Rx Bin: 004336 | Rx PCN: ADV
                    </div>
                </div>
            </td>

            <!-- BACK OF CARD -->
            <td style="width: 50%; vertical-align: top; padding-left: 12px;">
                <div style="font-size: 10px; font-weight: bold; color: #64748b; margin-bottom: 5px; text-transform: uppercase;">Reverso (Beneficios & Asistencia)</div>
                <div class="id-card" style="background-color: #ffffff; border-color: #94a3b8;">
                    <div style="font-size: 11px; font-weight: bold; color: #0f172a; margin-bottom: 6px;">
                        Copagos de Consulta (En Red)
                    </div>

                    <table style="width: 100%; font-size: 10px; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 2px 0;">Médico Primario (PCP):</td>
                            <td style="text-align: right; font-weight: bold; color: #0284c7;">
                                ${{ number_format((float) ($policy->quote?->copay_primary_care ?? 0), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0;">Médico Especialista:</td>
                            <td style="text-align: right; font-weight: bold;">
                                ${{ number_format((float) ($policy->quote?->copay_specialist ?? 0), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0;">Farmacia Genéricos (Tier 1):</td>
                            <td style="text-align: right; font-weight: bold;">
                                ${{ number_format((float) ($policy->quote?->copay_generic_drugs ?? 0), 2) }}
                            </td>
                        </tr>
                    </table>

                    <div style="margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 6px; font-size: 9px; color: #475569;">
                        <strong>Servicio al Cliente:</strong> {{ $support['phone'] }}<br>
                        <strong>Línea de Enfermería 24/7:</strong> {{ $support['nurse_line'] }}<br>
                        <strong>Agente Certificado:</strong> {{ $agent?->name }} (NPN: 19845210)
                    </div>

                    <div style="margin-top: 6px; font-size: 8px; color: #94a3b8;">
                        En caso de emergencia médica con peligro para la vida, llame al 911 o acuda a la sala de urgencias más cercana.
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="instruction-note">
        <strong>Instrucciones para el asegurado:</strong><br>
        1. Guarde este archivo o imprímalo para llevarlo en su billetera o vehículo.<br>
        2. Al acudir a su médico o farmacia, presente el <strong>Member ID</strong> y <strong>Group Number</strong> que figuran en el frente.<br>
        3. Para buscar doctores y hospitales dentro de su red, ingrese al portal de su aseguradora o llame al teléfono de atención al miembro.
    </div>

</body>
</html>
