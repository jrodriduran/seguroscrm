<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado CMS Scope of Appointment (SOA) - {{ $soa->beneficiary_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #1e293b;
            line-height: 1.5;
            margin: 40px;
        }
        .header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: table;
            width: 100%;
        }
        .header-cell {
            display: table-cell;
            vertical-align: middle;
        }
        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0 0 4px 0;
        }
        .subtitle {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 15px;
        }
        .grid {
            display: table;
            width: 100%;
        }
        .row {
            display: table-row;
        }
        .col {
            display: table-cell;
            width: 50%;
            padding: 4px 8px;
            vertical-align: top;
        }
        .label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .val {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }
        .rule-48h {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .products-list {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .product-item {
            font-size: 11px;
            padding: 3px 0;
            color: #1e293b;
        }
        .disclaimer {
            font-size: 10px;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .signature-box {
            border: 1px dashed #94a3b8;
            background: #ffffff;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            margin-bottom: 15px;
        }
        .signature-img {
            max-height: 70px;
        }
        .footer {
            margin-top: 20px;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
        @media print {
            body { margin: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    @if (! ($isPdf ?? false))
        <div class="no-print" style="text-align: right; margin-bottom: 15px; display: flex; justify-content: flex-end; gap: 8px;">
            <button onclick="window.print()" style="padding: 8px 16px; background: #1e3a8a; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                🖨️ Imprimir
            </button>
            <a href="{{ route('admin.leads.soa.certificate.pdf', $lead->id) }}" style="padding: 8px 16px; background: #16a34a; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">
                📥 Descargar PDF
            </a>
        </div>
    @endif

    <div class="header">
        <div class="header-cell">
            <h1 class="title">CMS SCOPE OF APPOINTMENT (SOA) COMPLIANCE CERTIFICATE</h1>
            <div class="subtitle">Certificado de Cumplimiento Regulatorio • 42 CFR § 422.2274 & § 423.2274</div>
        </div>
        <div class="header-right">
            <strong style="color: #16a34a; font-size: 13px;">ESTADO: AUTORIZADO Y FIRMADO</strong>
        </div>
    </div>

    <!-- 48-Hour CMS Rule Compliance Banner -->
    <div class="rule-48h">
        <strong style="color: #065f46; font-size: 12px;">REGULACIÓN CMS: PERÍODO OBLIGATORIO DE ESPERA DE 48 HORAS</strong>
        <div style="font-size: 11px; color: #047857; margin-top: 3px;">
            Fecha y Hora de Firma: <strong>{{ $soa->signed_at ? $soa->signed_at->format('d/m/Y h:i:s A') : 'N/A' }}</strong><br>
            Fecha Hábil para la Cita Personal: <strong>{{ $soa->appointment_eligible_at ? $soa->appointment_eligible_at->format('d/m/Y h:i:s A') : 'N/A' }}</strong>
            @if ($soa->exception_reason && $soa->exception_reason !== 'none')
                <div style="color: #b45309; font-weight: bold; margin-top: 2px;">
                    Excepción CMS Aplicada: {{ $soa->exception_reason === 'walk_in' ? 'Visita espontánea del beneficiario (Walk-in)' : 'Fin de período de enrolamiento' }}
                </div>
            @endif
        </div>
    </div>

    <div class="box">
        <div class="grid">
            <div class="row">
                <div class="col">
                    <span class="label">Beneficiario / Solicitante:</span>
                    <span class="val">{{ $soa->beneficiary_name }}</span>
                    <div style="font-size: 11px; color: #64748b;">Teléfono: {{ $soa->beneficiary_phone ?: 'No provisto' }}</div>
                    <div style="font-size: 11px; color: #64748b;">Medicare ID: {{ $soa->medicare_number ?: 'Registrado en expediente' }}</div>
                </div>
                <div class="col">
                    <span class="label">Agente Certificado Medicare:</span>
                    <span class="val">{{ $soa->agent_name }}</span>
                    <div style="font-size: 11px; color: #64748b;">NPN (National Producer Number): <strong>{{ $soa->agent_npn }}</strong></div>
                    <div style="font-size: 11px; color: #64748b;">Agencia: {{ $soa->agency_name ?: 'Seguros CRM Medicare' }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col" style="padding-top: 10px;">
                    <span class="label">Dirección IP Auditada:</span>
                    <span class="val" style="font-family: monospace;">{{ $soa->ip_address ?: '127.0.0.1' }}</span>
                </div>
                <div class="col" style="padding-top: 10px;">
                    <span class="label">Dispositivo / User-Agent:</span>
                    <span class="val" style="font-size: 10px; color: #64748b; font-weight: normal;">{{ $soa->user_agent ?: 'Web / Móvil' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Authorized by Beneficiary -->
    <div class="label" style="margin-bottom: 4px;">Productos Autorizados por el Beneficiario para Discutir:</div>
    <div class="products-list">
        @foreach ($soa->products_list as $product)
            <div class="product-item">
                ☑ <strong>{{ $product }}</strong>
            </div>
        @endforeach
    </div>

    <!-- Official TPMO Disclaimer -->
    <div class="disclaimer">
        <strong>CMS TPMO Disclaimer:</strong> {{ \Webkul\Lead\Models\LeadMedicareSoa::getTpmoDisclaimer() }}
    </div>

    <!-- Electronic Signature Proof -->
    <div class="signature-box">
        <div class="label">Constancia de Firma Electrónica del Beneficiario:</div>
        @if ($soa->signature_data)
            <img src="{{ $soa->signature_data }}" class="signature-img" alt="Firma Electrónica">
        @else
            <div style="color: #94a3b8; padding: 15px;">Sin firma registrada</div>
        @endif
        <div style="font-size: 10px; color: #64748b; margin-top: 5px;">
            Firmado por: <strong>{{ $soa->beneficiary_name }}</strong> conforme al Acta Federal ESIGN.
        </div>
    </div>

    <div class="footer">
        Este documento es un registro oficial de auditoría conforme a las regulaciones de CMS (Centers for Medicare & Medicaid Services).<br>
        Debe ser conservado por un mínimo de 10 años conforme a la ley federal. Identificador Token: {{ $soa->token }}
    </div>

</body>
</html>
