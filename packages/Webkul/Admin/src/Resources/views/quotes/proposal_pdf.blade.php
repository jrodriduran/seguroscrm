<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Propuesta Comparativa de Planes de Salud</title>
    <style>
        @page {
            margin: 20px 25px;
            size: letter portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: bold;
            color: #0369a1;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .brand-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 3px;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 16px;
            width: 100%;
        }
        .meta-table {
            width: 100%;
        }
        .meta-label {
            font-size: 10px;
            color: #64748b;
            font-weight: bold;
            text-transform: uppercase;
        }
        .meta-val {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }
        .subsidy-banner {
            background-color: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 8px 12px;
            margin-bottom: 18px;
            border-radius: 4px;
        }
        .subsidy-title {
            font-size: 11px;
            font-weight: bold;
            color: #065f46;
        }
        .subsidy-desc {
            font-size: 10px;
            color: #047857;
            margin-top: 2px;
        }
        .compare-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .compare-table th {
            background-color: #0f172a;
            color: #ffffff;
            padding: 10px 8px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #334155;
        }
        .compare-table th.feature-col {
            background-color: #1e293b;
            text-align: left;
            width: 25%;
        }
        .compare-table td {
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            font-size: 11px;
            text-align: center;
            vertical-align: middle;
        }
        .compare-table td.feature-title {
            text-align: left;
            font-weight: bold;
            background-color: #f8fafc;
            color: #334155;
        }
        .price-highlight {
            font-size: 16px;
            font-weight: 800;
            color: #0284c7;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-silver { background-color: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; }
        .badge-bronze { background-color: #ffedd5; color: #9a3412; border: 1px solid #fdba74; }
        .badge-gold { background-color: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        .badge-platinum { background-color: #f1f5f9; color: #0f172a; border: 1px solid #94a3b8; }
        .disclaimer-box {
            background-color: #fffbeb;
            border: 1px solid #fef3c7;
            border-radius: 4px;
            padding: 10px;
            font-size: 9px;
            color: #92400e;
            line-height: 1.4;
            margin-top: 15px;
        }
        .signatures {
            margin-top: 30px;
            width: 100%;
        }
        .sig-line {
            border-top: 1px solid #64748b;
            padding-top: 5px;
            text-align: center;
            font-size: 10px;
            color: #475569;
        }
        .action-button {
            display: inline-block;
            padding: 8px 16px;
            background-color: #0284c7;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    @if (! ($isPdf ?? false))
        <div style="text-align: right; margin-bottom: 15px; padding: 10px;">
            <a href="javascript:window.print()" class="action-button">🖨️ Imprimir Propuesta</a>
            <a href="{{ route('admin.quotes.proposals.download_pdf', $proposal->id) }}" class="action-button" style="background-color: #10b981;">📥 Descargar PDF</a>
        </div>
    @endif

    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <h1 class="brand-title">Propuesta Comparativa de Salud</h1>
                <div class="brand-subtitle">Mercado de Seguros Médicos ACA / Obamacare 2026</div>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div style="font-size: 13px; font-weight: bold; color: #0f172a;">{{ $agent?->name ?: 'Agente Certificado' }}</div>
                <div style="font-size: 10px; color: #64748b;">NPN: 19845210 | Licenciado en Seguros</div>
                <div style="font-size: 10px; color: #64748b;">Fecha: {{ now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td style="width: 50%;">
                    <div class="meta-label">Titular de la Póliza</div>
                    <div class="meta-val">{{ $lead->person?->name ?: $lead->title }}</div>
                    <div style="font-size: 10px; color: #64748b; margin-top: 2px;">
                        Teléfono: {{ $lead->person?->contact_numbers[0]['value'] ?? 'N/A' }} | Email: {{ $lead->person?->emails[0]['value'] ?? 'N/A' }}
                    </div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="meta-label">ID de Propuesta</div>
                    <div class="meta-val">#PROP-{{ str_pad($proposal->id, 5, '0', STR_PAD_LEFT) }}</div>
                    <div style="font-size: 10px; color: #64748b; margin-top: 2px;">
                        Enlace de Selección: <a href="{{ $proposal->public_url }}" style="color: #0284c7;">Ver en Línea</a>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @php
        $firstQuote = $quotes->first();
        $subsidyVal = (float) ($firstQuote?->aptc_subsidy ?? 0);
    @endphp

    @if ($subsidyVal > 0)
        <div class="subsidy-banner">
            <div class="subsidy-title">💰 Crédito Fiscal para Primas (Subsidio APTC del Gobierno Federal)</div>
            <div class="subsidy-desc">
                Su hogar califica para un subsidio mensual estimado de <strong>${{ number_format($subsidyVal, 2) }} / mes</strong>. Este descuento ya ha sido deducido directamente en las cuotas mensuales que se presentan abajo.
            </div>
        </div>
    @endif

    <table class="compare-table">
        <thead>
            <tr>
                <th class="feature-col">Detalle del Plan</th>
                @foreach ($quotes as $index => $quote)
                    <th>
                        Opción {{ $index + 1 }}<br>
                        <span style="font-size: 9px; font-weight: normal; color: #94a3b8;">{{ $quote->carrier_name ?: 'Aseguradora' }}</span>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="feature-title">Nombre del Plan</td>
                @foreach ($quotes as $quote)
                    <td style="font-weight: bold; color: #0f172a;">
                        {{ $quote->plan_name ?: ($quote->subject ?: 'Plan de Salud') }}
                    </td>
                @endforeach
            </tr>
            <tr>
                <td class="feature-title">Nivel de Metal & Red</td>
                @foreach ($quotes as $quote)
                    <td>
                        @php
                            $tier = strtolower($quote->metal_tier ?: 'silver');
                            $badgeClass = match($tier) {
                                'bronze' => 'badge-bronze',
                                'gold' => 'badge-gold',
                                'platinum' => 'badge-platinum',
                                default => 'badge-silver',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ strtoupper($tier) }}</span>
                        <span style="font-size: 10px; font-weight: bold; color: #475569; margin-left: 4px;">{{ strtoupper($quote->network_type ?: 'HMO') }}</span>
                    </td>
                @endforeach
            </tr>
            <tr style="background-color: #f0fdf4;">
                <td class="feature-title" style="color: #166534; font-size: 12px;">SU PAGO MENSUAL (Prima Neta)</td>
                @foreach ($quotes as $quote)
                    <td>
                        <div class="price-highlight">
                            ${{ number_format((float) ($quote->net_premium ?? $quote->grand_total), 2) }}
                        </div>
                        <div style="font-size: 9px; color: #64748b;">por mes</div>
                    </td>
                @endforeach
            </tr>
            <tr>
                <td class="feature-title">Prima Regular (Sin Subsidio)</td>
                @foreach ($quotes as $quote)
                    <td style="color: #64748b; text-decoration: line-through;">
                        ${{ number_format((float) ($quote->gross_premium ?? $quote->sub_total), 2) }}
                    </td>
                @endforeach
            </tr>
            <tr>
                <td class="feature-title">Deducible Médico Anual</td>
                @foreach ($quotes as $quote)
                    <td style="font-weight: bold;">
                        ${{ number_format((float) ($quote->deductible ?? 0), 2) }}
                    </td>
                @endforeach
            </tr>
            <tr>
                <td class="feature-title">Gasto Máximo de Bolsillo (MOOP)</td>
                @foreach ($quotes as $quote)
                    <td>
                        ${{ number_format((float) ($quote->out_of_pocket_max ?? 0), 2) }}
                    </td>
                @endforeach
            </tr>
            <tr>
                <td class="feature-title">Médico Primario (PCP)</td>
                @foreach ($quotes as $quote)
                    <td style="color: #0369a1; font-weight: bold;">
                        ${{ number_format((float) ($quote->copay_primary_care ?? 0), 2) }} Copago
                    </td>
                @endforeach
            </tr>
            <tr>
                <td class="feature-title">Médico Especialista</td>
                @foreach ($quotes as $quote)
                    <td>
                        ${{ number_format((float) ($quote->copay_specialist ?? 0), 2) }} Copago
                    </td>
                @endforeach
            </tr>
            <tr>
                <td class="feature-title">Medicamentos Genéricos (Tier 1)</td>
                @foreach ($quotes as $quote)
                    <td>
                        ${{ number_format((float) ($quote->copay_generic_drugs ?? 0), 2) }} Copago
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <div class="disclaimer-box">
        <strong>Aviso Importante & Validez de la Oferta:</strong> Las primas, créditos fiscales y beneficios mostrados están calculados en base a la información provista sobre ingresos familiares, código postal y composición del hogar. La inscripción formal está sujeta a verificación por parte del Mercado de Seguros Médicos (HealthCare.gov / State Exchange).
    </div>

    <table class="signatures">
        <tr>
            <td style="width: 45%; vertical-align: top;">
                <div style="height: 45px;"></div>
                <div class="sig-line">Firma del Cliente / Titular Aceptante</div>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%; vertical-align: top;">
                <div style="height: 45px;"></div>
                <div class="sig-line">Firma del Agente Certificado (NPN: 19845210)</div>
            </td>
        </tr>
    </table>

</body>
</html>
