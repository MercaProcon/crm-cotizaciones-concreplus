<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ __('Reporte Semanal de Cotizaciones Concreplus') }}</title>
<style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }

    .header { border-bottom: 3px solid #003060; padding-bottom: 8px; margin-bottom: 14px; }
    .header .logo-img { height: 32px; }
    .header .subtitle { font-size: 13px; font-weight: bold; color: #003060; margin-top: 4px; }
    .header table { width: 100%; }
    .header td { vertical-align: top; }
    .header .meta { text-align: right; font-size: 10px; color: #333; }
    .header .meta strong { color: #003060; }

    h2.section {
        background-color: #FDF1E1;
        color: #003060;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 4px 8px;
        margin: 14px 0 6px 0;
        border-left: 4px solid #003060;
    }

    table.summary { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    table.summary td { padding: 4px 8px; border: 1px solid #e2e8f0; width: 25%; }
    table.summary .label { color: #666; font-size: 9px; text-transform: uppercase; }
    table.summary .value { font-size: 13px; font-weight: bold; color: #111; }

    table.data { width: 100%; border-collapse: collapse; margin-bottom: 4px; font-size: 9px; }
    table.data th { background-color: #f3f4f6; color: #444; text-align: left; padding: 4px 6px; border: 1px solid #e2e8f0; }
    table.data td { padding: 4px 6px; border: 1px solid #e2e8f0; }

    .note { color: #999; font-size: 10px; }

    .observations { background-color: #F7F9FC; border: 1px solid #e2e8f0; border-left: 3px solid #003060; padding: 8px; font-size: 10px; color: #444; }
    .observations li { margin-bottom: 3px; }

    .footer { margin-top: 16px; font-size: 8px; color: #999; text-align: center; border-top: 1px solid #ddd; padding-top: 6px; }
</style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <img class="logo-img" src="{{ $logoDataUri }}" alt="Concreplus">
                    <div class="subtitle">{{ __('Reporte Semanal de Cotizaciones Concreplus') }}</div>
                </td>
                <td class="meta">
                    <strong>{{ __('Periodo') }}:</strong> {{ $periodStart->format('d/m/Y') }} — {{ $periodEnd->format('d/m/Y') }}<br>
                    <strong>{{ __('Generado') }}:</strong> {{ $generatedAt->format('d/m/Y H:i') }}
                </td>
            </tr>
        </table>
        <p class="note">{{ __('Periodo calculado con base en la fecha de registro de la cotización (semana anterior completa, lunes a domingo).') }}</p>
        <p class="note">{{ __('Solo se incluyen cotizaciones desde el :date en adelante.', ['date' => \Illuminate\Support\Carbon::parse(\App\Models\Quotation::operationalStartDate())->format('d/m/Y')]) }}</p>
    </div>

    <h2 class="section">{{ __('Resumen ejecutivo') }}</h2>
    @if ($summary['total'] === 0)
        <p class="note">{{ __('No hubo cotizaciones registradas en este periodo.') }}</p>
    @else
        <table class="summary">
            <tr>
                <td><div class="label">{{ __('Total recibidas') }}</div><div class="value">{{ $summary['total'] }}</div></td>
                <td><div class="label">{{ __('Asignadas') }}</div><div class="value">{{ $summary['assigned'] }}</div></td>
                <td><div class="label">{{ __('Atendidas') }}</div><div class="value">{{ $summary['attended'] }}</div></td>
                <td><div class="label">{{ __('Sin seguimiento') }}</div><div class="value">{{ $summary['without_follow_up'] }}</div></td>
            </tr>
            <tr>
                <td><div class="label">{{ __('Ganadas') }}</div><div class="value">{{ $summary['won'] }}</div></td>
                <td><div class="label">{{ __('Perdidas') }}</div><div class="value">{{ $summary['lost'] }}</div></td>
                <td><div class="label">{{ __('Conversión') }}</div><div class="value">{{ $summary['conversion'] !== null ? $summary['conversion'].'%' : __('Sin datos') }}</div></td>
                <td><div class="label">{{ __('T. prom. atención') }}</div><div class="value">{{ $summary['avg_attention_minutes'] !== null ? $summary['avg_attention_minutes'].' min' : __('Sin datos') }}</div></td>
            </tr>
        </table>

        <h2 class="section">{{ __('Desempeño por vendedora') }}</h2>
        @if ($byVendedora->isEmpty())
            <p class="note">{{ __('Sin asignaciones registradas en el periodo.') }}</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>{{ __('Vendedora') }}</th>
                        <th>{{ __('Asig.') }}</th>
                        <th>{{ __('Atend.') }}</th>
                        <th>{{ __('Sin seg.') }}</th>
                        <th>{{ __('Ganadas') }}</th>
                        <th>{{ __('Perdidas') }}</th>
                        <th>{{ __('Conv.') }}</th>
                        <th>{{ __('T. atención') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($byVendedora as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['assigned'] }}</td>
                            <td>{{ $row['attended'] }}</td>
                            <td>{{ $row['without_follow_up'] }}</td>
                            <td>{{ $row['won'] }}</td>
                            <td>{{ $row['lost'] }}</td>
                            <td>{{ $row['conversion'] !== null ? $row['conversion'].'%' : '—' }}</td>
                            <td>{{ $row['avg_attention_minutes'] !== null ? $row['avg_attention_minutes'].' min' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h2 class="section">{{ __('Alertas comerciales') }}</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>{{ __('Tipo de alerta') }}</th>
                    <th>{{ __('Cantidad') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>{{ __('Sin seguimiento') }}</td><td>{{ $alerts['without_follow_up']->count() }}</td></tr>
                <tr><td>{{ __('Más de 3 días sin seguimiento') }}</td><td>{{ $alerts['over_3_days']->count() }}</td></tr>
                <tr><td>{{ __('Pendientes de respuesta') }}</td><td>{{ $alerts['pending_response']->count() }}</td></tr>
                <tr><td>{{ __('Críticas para seguimiento (más de :days días)', ['days' => \App\Services\CommercialReportService::CRITICAL_FOLLOW_UP_DAYS]) }}</td><td>{{ $alerts['critical']->count() }}</td></tr>
            </tbody>
        </table>

        @if ($alerts['critical']->isNotEmpty())
            <table class="data">
                <thead>
                    <tr>
                        <th>{{ __('Folio') }}</th>
                        <th>{{ __('Cliente') }}</th>
                        <th>{{ __('Vendedora') }}</th>
                        <th>{{ __('Días sin seguimiento') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($alerts['critical']->take(20) as $row)
                        <tr>
                            <td>{{ $row['quotation']->folio }}</td>
                            <td>{{ $row['quotation']->client_name }}</td>
                            <td>{{ $row['vendedora'] }}</td>
                            <td>{{ $row['days_without_follow_up'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($alerts['critical']->count() > 20)
                <p class="note">{{ __('Hay más casos críticos. Revisa el Panel de asignación y Reportes en el CRM.') }}</p>
            @endif
        @endif

        <h2 class="section">{{ __('Observaciones automáticas') }}</h2>
        <div class="observations">
            <ul>
                <li>{{ __('Vendedora con más cotizaciones ganadas') }}: {{ $observations['top_vendedora'] ?? __('Sin datos') }}</li>
                <li>{{ __('Estado dominante del pipeline') }}: {{ $observations['top_status'] ?? __('Sin datos') }}</li>
                <li>{{ __('Mayor cantidad de cotizaciones sin seguimiento') }}: {{ $observations['top_without_follow_up'] ?? __('Sin datos') }}</li>
            </ul>
        </div>
    @endif

    <div class="footer">
        {{ __('Documento generado automáticamente por el CRM Cotizaciones Concreplus.') }}
    </div>

</body>
</html>
