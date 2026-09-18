<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Consentimiento CMS - {{ $lead->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #1e293b;
            line-height: 1.5;
            margin: 40px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 25px;
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
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0 0 4px 0;
        }
        .subtitle {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
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
            padding: 6px 10px;
            vertical-align: top;
        }
        .label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .val {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        .legal-text {
            font-size: 11px;
            color: #334155;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 12px;
            border-radius: 4px;
            white-space: pre-line;
            margin-bottom: 20px;
        }
        .signature-box {
            border: 1px dashed #94a3b8;
            background: #ffffff;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }
        .signature-img {
            max-height: 80px;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #cbd5e1;
            padding-top: 15px;
            font-size: 10px;
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
            <button onclick="window.print()" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                🖨️ Imprimir
            </button>
            <a href="{{ route('admin.leads.consent.certificate.pdf', $lead->id) }}" style="padding: 8px 16px; background: #16a34a; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">
                📥 Descargar PDF
            </a>
        </div>
    @endif

    <div class="header">
        <div class="header-cell">
            <h1 class="title">CERTIFICADO DE AUTORIZACIÓN Y CONSENTIMIENTO CMS</h1>
            <div class="subtitle">Cumplimiento Federal 45 CFR § 155.220 • ACA / Healthcare.gov</div>
        </div>
        <div class="header-right">
            <strong style="color: #16a34a; font-size: 14px;">ESTADO: FIRMADO ELECTRÓNICAMENTE</strong>
        </div>
    </div>

    <div class="box">
        <div class="grid">
            <div class="row">
                <div class="col">
                    <span class="label">Consumidor Titular:</span>
                    <span class="val">{{ $consent->client_name }}</span>
                    <div style="font-size: 11px; color: #64748b;">Teléfono: {{ $consent->client_phone ?: 'No provisto' }}</div>
                    <div style="font-size: 11px; color: #64748b;">Email: {{ $consent->client_email ?: 'No provisto' }}</div>
                </div>
                <div class="col">
                    <span class="label">Agente Certificado:</span>
                    <span class="val">{{ $consent->agent_name }}</span>
                    <div style="font-size: 11px; color: #64748b;">NPN (National Producer Number): <strong>{{ $consent->agent_npn }}</strong></div>
                    <div style="font-size: 11px; color: #64748b;">Agencia: {{ $consent->agency_name ?: 'Seguros CRM' }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col" style="padding-top: 15px;">
                    <span class="label">Fecha y Hora de Firma:</span>
                    <span class="val">{{ $consent->signed_at ? $consent->signed_at->format('d/m/Y h:i:s A') : 'N/A' }}</span>
                </div>
                <div class="col" style="padding-top: 15px;">
                    <span class="label">Dirección IP del Firmante:</span>
                    <span class="val" style="font-family: monospace;">{{ $consent->ip_address ?: '127.0.0.1' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="label" style="margin-bottom: 5px;">Texto Legal de Autorización:</div>
    <div class="legal-text">
        {!! nl2br(e($consent->consent_text)) !!}
    </div>

    <div class="signature-box">
        <div class="label">Constancia de Firma Electrónica:</div>
        @if ($consent->signature_data)
            <img src="{{ $consent->signature_data }}" class="signature-img" alt="Firma Electrónica">
        @else
            <div style="color: #94a3b8; padding: 20px;">Sin firma registrada</div>
        @endif
        <div style="font-size: 11px; color: #64748b; margin-top: 8px;">
            Firmado por: <strong>{{ $consent->client_name }}</strong> • Dispositivo: {{ $consent->user_agent }}
        </div>
    </div>

    <div class="footer">
        Este documento es un registro oficial de auditoría conforme al Acta Federal ESIGN y las regulaciones del CMS.<br>
        Identificador Único (Token): {{ $consent->token }}
    </div>

</body>
</html>
