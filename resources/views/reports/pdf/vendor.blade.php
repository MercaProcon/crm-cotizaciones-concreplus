<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ __('Reporte individual de vendedora') }} — {{ $vendedora->name }}</title>
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
        background-color: #EAF4EF;
        color: #003060;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 4px 8px;
        margin: 14px 0 6px 0;
        border-left: 4px solid #003060;
    }

    table.summary { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    table.summary td { padding: 4px 8px; border: 1px solid #e2e8f0; width: 20%; }
    table.summary .label { color: #666; font-size: 9px; text-transform: uppercase; }
    table.summary .value { font-size: 13px; font-weight: bold; color: #111; }

    table.data { width: 100%; border-collapse: collapse; margin-bottom: 4px; font-size: 9px; }
    table.data th { background-color: #f3f4f6; color: #444; text-align: left; padding: 4px 6px; border: 1px solid #e2e8f0; }
    table.data td { padding: 4px 6px; border: 1px solid #e2e8f0; }

    .note { color: #999; font-size: 10px; }

    .alert-list { font-size: 9px; color: #444; margin: 0 0 8px 0; padding-left: 14px; }
    .alert-list li { margin-bottom: 2px; }

    .footer { margin-top: 16px; font-size: 8px; color: #999; text-align: center; border-top: 1px solid #ddd; padding-top: 6px; }
</style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <img class="logo-img" src="{{ $logoDataUri }}" alt="Concreplus">
                    <div class="subtitle">{{ __('Reporte individual de vendedora') }}</div>
                </td>
                <td class="meta">
                    <strong>{{ __('Vendedora') }}:</strong> {{ $vendedora->name }}<br>
                    <strong>{{ __('Periodo') }}:</strong>
                    {{ $filters['start_date'] ?: __('inicio') }} — {{ $filters['end_date'] ?: __('hoy') }}<br>
                    <strong>{{ __('Generado') }}:</strong> {{ $generatedAt->format('d/m/Y H:i') }}
                </td>
            </tr>
        </table>
        <p class="note">{{ __('Periodo calculado con base en la fecha de registro de la cotización.') }}</p>
        <p class="note">{{ __('Solo se incluyen cotizaciones desde el :date en adelante.', ['date' => \Illuminate\Support\Carbon::parse(\App\Models\Quotation::operationalStartDate())->format('d/m/Y')]) }}</p>
    </div>

    @if ($metrics['assigned'] === 0)
        <p class="note">{{ __('Esta vendedora no tiene cotizaciones asignadas en el periodo y filtros seleccionados.') }}</p>
    @else
        <h2 class="section">{{ __('Resumen de indicadores') }}</h2>
        <table class="summary">
            <tr>
                <td><div class="label">{{ __('Asignadas') }}</div><div class="value">{{ $metrics['assigned'] }}</div></td>
                <td><div class="label">{{ __('Atendidas') }}</div><div class="value">{{ $metrics['attended'] }}</div></td>
                <td><div class="label">{{ __('Sin seguimiento') }}</div><div class="value">{{ $metrics['without_follow_up'] }}</div></td>
                <td><div class="label">{{ __('En seguimiento') }}</div><div class="value">{{ $metrics['en_seguimiento'] }}</div></td>
                <td><div class="label">{{ __('Cot. enviada') }}</div><div class="value">{{ $metrics['cotizacion_enviada'] }}</div></td>
            </tr>
            <tr>
                <td><div class="label">{{ __('Pendientes') }}</div><div class="value">{{ $metrics['pendiente_respuesta'] }}</div></td>
                <td><div class="label">{{ __('Ganadas') }}</div><div class="value">{{ $metrics['won'] }}</div></td>
                <td><div class="label">{{ __('Perdidas') }}</div><div class="value">{{ $metrics['lost'] }}</div></td>
                <td><div class="label">{{ __('Conversión') }}</div><div class="value">{{ $metrics['conversion'] !== null ? $metrics['conversion'].'%' : __('Sin datos') }}</div></td>
                <td><div class="label">{{ __('T. prom. atención') }}</div><div class="value">{{ $metrics['avg_attention_minutes'] !== null ? $metrics['avg_attention_minutes'].' min' : __('Sin datos') }}</div></td>
            </tr>
        </table>

        <h2 class="section">{{ __('Cotizaciones asignadas') }}</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>{{ __('Folio') }}</th>
                    <th>{{ __('Cliente') }}</th>
                    <th>{{ __('Teléfono') }}</th>
                    <th>{{ __('Fecha registro') }}</th>
                    <th>{{ __('Fecha asignación') }}</th>
                    <th>{{ __('Estado CRM') }}</th>
                    <th>{{ __('Último seguimiento') }}</th>
                    <th>{{ __('Días sin seg.') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($metrics['rows'] as $row)
                    <tr>
                        <td>{{ $row['quotation']->folio }}</td>
                        <td>{{ $row['quotation']->client_name }}</td>
                        <td>{{ $row['quotation']->phone_raw }}</td>
                        <td>{{ ($row['quotation']->registered_at ?? $row['quotation']->created_at)->format('d/m/Y') }}</td>
                        <td>{{ $row['assignment']->assigned_at->format('d/m/Y') }}</td>
                        <td>{{ $statusLabels[$row['quotation']->crm_status] }}</td>
                        <td>{{ $row['last_follow_up'] ? $row['last_follow_up']->created_at->format('d/m/Y') : '—' }}</td>
                        <td>{{ $row['days_without_follow_up'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2 class="section">{{ __('Cotizaciones sin seguimiento') }}</h2>
        @if ($metrics['without_follow_up_rows']->isEmpty())
            <p class="note">{{ __('Sin pendientes.') }}</p>
        @else
            <ul class="alert-list">
                @foreach ($metrics['without_follow_up_rows'] as $row)
                    <li>{{ $row['quotation']->folio }} — {{ $row['quotation']->client_name }} ({{ $row['days_without_follow_up'] }} {{ __('días') }})</li>
                @endforeach
            </ul>
        @endif

        <h2 class="section">{{ __('Más de 3 días sin seguimiento') }}</h2>
        @if ($metrics['over_3_days_rows']->isEmpty())
            <p class="note">{{ __('Sin pendientes.') }}</p>
        @else
            <ul class="alert-list">
                @foreach ($metrics['over_3_days_rows'] as $row)
                    <li>{{ $row['quotation']->folio }} — {{ $row['quotation']->client_name }} ({{ $row['days_without_follow_up'] }} {{ __('días') }})</li>
                @endforeach
            </ul>
        @endif

        <h2 class="section">{{ __('Comentarios recientes de seguimiento') }}</h2>
        @if ($metrics['lost_with_comment_rows']->isEmpty())
            <p class="note">{{ __('Sin comentarios de cotizaciones perdidas.') }}</p>
        @else
            <ul class="alert-list">
                @foreach ($metrics['lost_with_comment_rows'] as $row)
                    <li>{{ $row['quotation']->folio }} — {{ $row['quotation']->client_name }}: {{ $row['comment'] }}</li>
                @endforeach
            </ul>
        @endif
    @endif

    <div class="footer">
        {{ __('Documento generado automáticamente por el CRM Cotizaciones Concreplus.') }}
    </div>

</body>
</html>
