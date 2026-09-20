<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>@lang('admin::insurance.rx_and_doctors.pdf_title') - {{ $lead->person?->name ?: $lead->title }}</title>
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
                <div class="logo-title">@lang('admin::insurance.rx_and_doctors.pdf_header_title')</div>
                <div class="subtitle">@lang('admin::insurance.rx_and_doctors.pdf_header_subtitle')</div>
            </td>
            <td style="width: 35%; text-align: right;">
                <div style="font-size: 10px; color: #64748b;">@lang('admin::insurance.rx_and_doctors.pdf_issue_date') <strong>{{ $generatedAt }}</strong></div>
                <div style="font-size: 10px; color: #64748b;">@lang('admin::insurance.rx_and_doctors.pdf_agent') <strong>{{ $lead->user?->name ?: 'Prime Health CRM' }}</strong></div>
            </td>
        </tr>
    </table>

    <!-- Beneficiary Details -->
    <div class="meta-box">
        <table>
            <tr>
                <td style="width: 25%;">
                    <div class="meta-label">@lang('admin::insurance.rx_and_doctors.pdf_beneficiary')</div>
                    <div class="meta-value">{{ $lead->person?->name ?: $lead->title }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="meta-label">@lang('admin::insurance.rx_and_doctors.pdf_phone')</div>
                    <div class="meta-value">{{ data_get($lead->person?->contact_numbers, '0.value', 'N/A') }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="meta-label">@lang('admin::insurance.rx_and_doctors.pdf_total_meds')</div>
                    <div class="meta-value">{{ trans('admin::insurance.rx_and_doctors.pdf_meds_count', ['count' => $medications->count()]) }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="meta-label">@lang('admin::insurance.rx_and_doctors.pdf_monthly_copay')</div>
                    <div class="meta-value" style="color: #0369a1;">${{ number_format($medications->sum('estimated_copay_30d'), 2) }} @lang('admin::insurance.rx_and_doctors.pdf_per_month')</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 1. Formulary / Prescription Medications -->
    <div class="section-title">@lang('admin::insurance.rx_and_doctors.pdf_section_1')</div>

    @if ($medications->isEmpty())
        <div style="padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; color: #64748b; font-style: italic;">
            @lang('admin::insurance.rx_and_doctors.pdf_no_meds')
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 28%;">@lang('admin::insurance.rx_and_doctors.pdf_col_drug')</th>
                    <th style="width: 14%;">@lang('admin::insurance.rx_and_doctors.pdf_col_dosage')</th>
                    <th style="width: 12%;">@lang('admin::insurance.rx_and_doctors.pdf_col_qty')</th>
                    <th style="width: 16%;">@lang('admin::insurance.rx_and_doctors.pdf_col_tier')</th>
                    <th style="width: 15%;">@lang('admin::insurance.rx_and_doctors.pdf_col_restrictions')</th>
                    <th style="width: 15%; text-align: right;">@lang('admin::insurance.rx_and_doctors.pdf_col_copay')</th>
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
                        <td>{{ $med->quantity_per_30_days }} @lang('admin::insurance.rx_and_doctors.pdf_units')</td>
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
                                <span style="color: #10b981; font-size: 8px;">@lang('admin::insurance.rx_and_doctors.pdf_no_restrictions')</span>
                            @else
                                @foreach ($codes as $c)
                                    <span class="badge badge-warn" title="{{ $c['label'] }}">{{ $c['code'] }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <strong>${{ number_format($med->estimated_copay_30d, 2) }}</strong><br>
                            <span style="font-size: 8px; color: #0284c7;">@lang('admin::insurance.rx_and_doctors.pdf_mail_90d') ${{ number_format($med->estimated_copay_90d_mail, 2) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- 2. Doctors & Providers Network -->
    <div class="section-title" style="margin-top: 20px;">@lang('admin::insurance.rx_and_doctors.pdf_section_2')</div>

    @if ($doctors->isEmpty())
        <div style="padding: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; color: #64748b; font-style: italic;">
            @lang('admin::insurance.rx_and_doctors.pdf_no_docs')
        </div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">@lang('admin::insurance.rx_and_doctors.pdf_col_doctor')</th>
                    <th style="width: 20%;">@lang('admin::insurance.rx_and_doctors.pdf_col_specialty')</th>
                    <th style="width: 25%;">@lang('admin::insurance.rx_and_doctors.pdf_col_clinic')</th>
                    <th style="width: 30%;">@lang('admin::insurance.rx_and_doctors.pdf_col_status')</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($doctors as $doc)
                    <tr>
                        <td>
                            <strong>{{ $doc->doctor_name }}</strong>
                            @if ($doc->is_primary_physician)
                                <span class="badge badge-t1">@lang('admin::insurance.rx_and_doctors.pdf_pcp_badge')</span>
                            @endif
                            @if ($doc->phone)
                                <div style="font-size: 8px; color: #64748b;">@lang('admin::insurance.rx_and_doctors.pdf_tel') {{ $doc->phone }}</div>
                            @endif
                        </td>
                        <td>{{ $doc->specialty }}</td>
                        <td>
                            {{ $doc->clinic_or_hospital ?: trans('admin::insurance.rx_and_doctors.pdf_private_practice') }}<br>
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
                                <span style="color: #64748b; font-size: 8px;">@lang('admin::insurance.rx_and_doctors.pdf_in_verification')</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Compliance Disclaimer -->
    <div style="margin-top: 25px; padding: 10px; background-color: #f8fafc; border-left: 3px solid #0284c7; font-size: 8px; color: #475569;">
        <strong>@lang('admin::insurance.rx_and_doctors.pdf_disclaimer_title')</strong> @lang('admin::insurance.rx_and_doctors.pdf_disclaimer_body')
    </div>

    <!-- Footer -->
    <div class="footer">
        @lang('admin::insurance.rx_and_doctors.pdf_footer')
    </div>

</body>
</html>
