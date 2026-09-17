<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="{{ $locale = app()->getLocale() }}">
    <head>
        <meta http-equiv="Cache-control" content="no-cache" />
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <style type="text/css">
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: Arial, Helvetica, sans-serif;
            }

            body {
                font-size: 10px;
                color: #1e293b;
                line-height: 1.4;
                padding: 24px;
            }

            .header-table {
                width: 100%;
                border-bottom: 2px solid #2563eb;
                padding-bottom: 12px;
                margin-bottom: 16px;
            }

            .agency-title {
                font-size: 20px;
                font-weight: bold;
                color: #1e3a8a;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .proposal-badge {
                text-align: right;
                font-size: 12px;
                font-weight: bold;
                color: #2563eb;
                text-transform: uppercase;
            }

            .meta-table {
                width: 100%;
                margin-bottom: 16px;
                border-collapse: collapse;
            }

            .meta-card {
                width: 48%;
                background-color: #f8fafc;
                border: 1px solid #e2e8f0;
                padding: 10px 12px;
                vertical-align: top;
            }

            .meta-card-title {
                font-size: 11px;
                font-weight: bold;
                color: #1e40af;
                border-bottom: 1px solid #cbd5e1;
                padding-bottom: 4px;
                margin-bottom: 6px;
                text-transform: uppercase;
            }

            .meta-row {
                font-size: 9.5px;
                margin-bottom: 3px;
            }

            .meta-label {
                font-weight: bold;
                color: #475569;
                display: inline-block;
                width: 100px;
            }

            .meta-value {
                color: #0f172a;
            }

            .section-title {
                font-size: 12px;
                font-weight: bold;
                color: #0f172a;
                margin-top: 14px;
                margin-bottom: 8px;
                padding-left: 6px;
                border-left: 3px solid #2563eb;
                text-transform: uppercase;
            }

            .plans-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 16px;
            }

            .plans-table thead th {
                background-color: #1e40af;
                color: #ffffff;
                font-size: 9px;
                font-weight: bold;
                text-transform: uppercase;
                padding: 8px 10px;
                text-align: left;
            }

            .plans-table tbody td {
                padding: 8px 10px;
                border-bottom: 1px solid #e2e8f0;
                font-size: 9.5px;
                vertical-align: middle;
            }

            .plans-table tbody tr:nth-child(even) {
                background-color: #f8fafc;
            }

            .plan-name {
                font-weight: bold;
                color: #0f172a;
                font-size: 10px;
            }

            .plan-carrier {
                font-size: 8.5px;
                color: #2563eb;
                font-weight: 600;
            }

            .badge {
                display: inline-block;
                padding: 2px 6px;
                font-size: 8px;
                font-weight: bold;
                border-radius: 3px;
                background-color: #e0e7ff;
                color: #3730a3;
            }

            .text-right {
                text-align: right;
            }

            .text-center {
                text-align: center;
            }

            .price-highlight {
                font-size: 11px;
                font-weight: bold;
                color: #047857;
            }

            .summary-container {
                width: 100%;
                margin-top: 8px;
                margin-bottom: 20px;
            }

            .summary-table {
                float: right;
                width: 280px;
                border-collapse: collapse;
                border: 1px solid #cbd5e1;
            }

            .summary-table td {
                padding: 6px 12px;
                font-size: 9.5px;
            }

            .summary-table tr.total-row {
                background-color: #1e40af;
                color: #ffffff;
                font-weight: bold;
                font-size: 11px;
            }

            .summary-table tr.total-row td {
                color: #ffffff;
                padding: 8px 12px;
            }

            .disclaimer-box {
                clear: both;
                margin-top: 30px;
                padding: 10px 12px;
                background-color: #f1f5f9;
                border: 1px solid #cbd5e1;
                border-radius: 4px;
            }

            .disclaimer-title {
                font-size: 9px;
                font-weight: bold;
                color: #334155;
                text-transform: uppercase;
                margin-bottom: 4px;
            }

            .disclaimer-text {
                font-size: 8px;
                color: #64748b;
                line-height: 1.35;
            }
        </style>
    </head>

    <body>
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 55%; vertical-align: middle;">
                    <div class="agency-title">
                        {{ config('app.name', 'Seguros CRM') }}
                    </div>
                    <div style="font-size: 9px; color: #64748b; margin-top: 2px;">
                        @lang('admin::insurance.quotes_pdf.subtitle')
                    </div>
                </td>

                <td style="width: 45%; vertical-align: middle;" class="proposal-badge">
                    <div>@lang('admin::insurance.quotes_pdf.title')</div>
                    <div style="font-size: 9.5px; color: #475569; font-weight: normal; margin-top: 3px;">
                        #{{ $quote->id }} &bull; {{ $quote->created_at->format('d/m/Y') }}
                    </div>
                </td>
            </tr>
        </table>

        <!-- Metadata Section (Applicant & Agent) -->
        <table class="meta-table">
            <tr>
                <!-- Applicant Info -->
                <td class="meta-card" style="width: 49%;">
                    <div class="meta-card-title">
                        @lang('admin::insurance.quotes_pdf.applicant_info')
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">@lang('admin::app.quotes.index.pdf.person'):</span>
                        <span class="meta-value"><b>{{ $quote->person?->name ?? 'N/A' }}</b></span>
                    </div>
                    @if (! empty($quote->person?->emails))
                        <div class="meta-row">
                            <span class="meta-label">Email:</span>
                            <span class="meta-value">{{ $quote->person->emails[0]['value'] ?? '' }}</span>
                        </div>
                    @endif
                    @if (! empty($quote->person?->contact_numbers))
                        <div class="meta-row">
                            <span class="meta-label">Phone:</span>
                            <span class="meta-value">{{ $quote->person->contact_numbers[0]['value'] ?? '' }}</span>
                        </div>
                    @endif
                    @php
                        $lead = $quote->leads->first();
                    @endphp
                    @if ($lead)
                        <div class="meta-row">
                            <span class="meta-label">Case / Lead:</span>
                            <span class="meta-value">{{ $lead->title }}</span>
                        </div>
                    @endif
                </td>

                <td style="width: 2%;"></td>

                <!-- Agent & Proposal Validity -->
                <td class="meta-card" style="width: 49%;">
                    <div class="meta-card-title">
                        @lang('admin::insurance.quotes_pdf.agent_info')
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">@lang('admin::app.quotes.index.pdf.sales-person'):</span>
                        <span class="meta-value"><b>{{ $quote->user?->name ?? 'Agency Agent' }}</b></span>
                    </div>
                    @if ($quote->user?->email)
                        <div class="meta-row">
                            <span class="meta-label">Agent Email:</span>
                            <span class="meta-value">{{ $quote->user->email }}</span>
                        </div>
                    @endif
                    <div class="meta-row">
                        <span class="meta-label">@lang('admin::app.quotes.index.pdf.subject'):</span>
                        <span class="meta-value">{{ $quote->subject }}</span>
                    </div>
                    @if ($quote->expired_at)
                        <div class="meta-row">
                            <span class="meta-label">Valid Until:</span>
                            <span class="meta-value" style="color: #b91c1c; font-weight: bold;">
                                {{ $quote->expired_at->format('d/m/Y') }}
                            </span>
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        @if ($quote->description)
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; margin-bottom: 14px; font-size: 9px; color: #334155;">
                <b>Notes / Observaciones:</b> {{ $quote->description }}
            </div>
        @endif

        <!-- Plans & Benefits Table -->
        <div class="section-title">
            @lang('admin::insurance.quotes_pdf.plan_details')
        </div>

        <table class="plans-table">
            <thead>
                <tr>
                    <th style="width: 34%;">@lang('admin::insurance.quotes_pdf.plan_name')</th>
                    <th style="width: 14%;">@lang('admin::insurance.quotes_pdf.network_tier')</th>
                    <th style="width: 13%;" class="text-right">@lang('admin::insurance.quotes_pdf.deductible')</th>
                    <th style="width: 13%;" class="text-right">@lang('admin::insurance.quotes_pdf.moop')</th>
                    <th style="width: 12%;" class="text-center">@lang('admin::insurance.quotes_pdf.copays')</th>
                    <th style="width: 14%;" class="text-right">@lang('admin::insurance.quotes_pdf.monthly_premium')</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quote->items as $item)
                    @php
                        $product = $item->product;
                        $deductible = $product?->deductible ? '$'.number_format($product->deductible, 0) : '$0';
                        $moop = $product?->max_out_of_pocket ? '$'.number_format($product->max_out_of_pocket, 0) : '$0';
                        $pcp = $product?->primary_care_copay ?? '$0';
                        $spec = $product?->specialist_copay ?? '$0';
                    @endphp
                    <tr>
                        <td>
                            <div class="plan-name">{{ $item->name }}</div>
                            <div class="plan-carrier">SKU: {{ $item->sku }}</div>
                        </td>

                        <td>
                            <span class="badge">
                                {{ $product?->network_type ?? 'PPO/HMO' }}
                            </span>
                        </td>

                        <td class="text-right font-medium">
                            {{ $deductible }}
                        </td>

                        <td class="text-right font-medium">
                            {{ $moop }}
                        </td>

                        <td class="text-center" style="font-size: 8.5px;">
                            {{ $pcp }} / {{ $spec }}
                        </td>

                        <td class="text-right price-highlight">
                            {!! core()->formatBasePrice($item->total, true) !!} / mo
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Calculation Box -->
        <div class="summary-container">
            <table class="summary-table">
                <tbody>
                    <tr>
                        <td style="font-weight: bold; color: #475569;">
                            @lang('admin::app.quotes.index.pdf.sub-total'):
                        </td>
                        <td class="text-right font-medium">
                            {!! core()->formatBasePrice($quote->sub_total, true) !!}
                        </td>
                    </tr>

                    @if ($quote->discount_amount > 0)
                        <tr>
                            <td style="font-weight: bold; color: #047857;">
                                Subsidio / Descuento:
                            </td>
                            <td class="text-right" style="color: #047857; font-weight: bold;">
                                -{!! core()->formatBasePrice($quote->discount_amount, true) !!}
                            </td>
                        </tr>
                    @endif

                    @if ($quote->adjustment_amount != 0)
                        <tr>
                            <td style="font-weight: bold; color: #475569;">
                                Ajuste / Cargo:
                            </td>
                            <td class="text-right font-medium">
                                {!! core()->formatBasePrice($quote->adjustment_amount, true) !!}
                            </td>
                        </tr>
                    @endif

                    <tr class="total-row">
                        <td>
                            @lang('admin::insurance.quotes_pdf.total_monthly'):
                        </td>
                        <td class="text-right">
                            {!! core()->formatBasePrice($quote->grand_total, true) !!} / mo
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Compliance Disclaimer -->
        <div class="disclaimer-box">
            <div class="disclaimer-title">
                @lang('admin::insurance.quotes_pdf.disclaimer_title')
            </div>
            <div class="disclaimer-text">
                @lang('admin::insurance.quotes_pdf.disclaimer_text')
            </div>
        </div>
    </body>
</html>