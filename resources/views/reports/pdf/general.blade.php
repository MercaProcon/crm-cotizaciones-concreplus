<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ __('Reporte Comercial de Cotizaciones') }}</title>
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
                    <div class="subtitle">{{ __('Reporte Comercial de Cotizaciones') }}</div>
                </td>
                <td class="meta">
                    <strong>{{ __('Periodo') }}:</strong>
                    {{ $filters['start_date'] ?: __('inicio') }} — {{ $filters['end_date'] ?: __('hoy') }}<br>
                    <strong>{{ __('Generado') }}:</strong> {{ $generatedAt->format('d/m/Y H:i') }}
                </td>
            </tr>
        </table>
        <p class="note">{{ __('Periodo calculado con base en la fecha de registro de la cotización.') }}</p>
        <p class="note">{{ __('Solo se incluyen cotizaciones desde el :date en adelante.', ['date' => \Illuminate\Support\Carbon::parse(\App\Models\Quotation::operationalStartDate())->format('d/m/Y')]) }}</p>
    </div>

    <h2 class="section">{{ __('Resumen ejecutivo') }}</h2>
    @if ($summary['total'] === 0)
        <p class="note">{{ __('No hay cotizaciones que coincidan con los filtros seleccionados.') }}</p>
    @else
        <table class="summary">
            <tr>
                <td><div class="label">{{ __('Total') }}</div><div class="value">{{ $summary['total'] }}</div></td>
                <td><div class="label">{{ __('Nuevas') }}</div><div class="value">{{ $summary['nueva'] }}</div></td>
                <td><div class="label">{{ __('Asignadas') }}</div><div class="value">{{ $summary['asignada'] }}</div></td>
                <td><div class="label">{{ __('En seguimiento') }}</div><div class="value">{{ $summary['en_seguimiento'] }}</div></td>
            </tr>
            <tr>
                <td><div class="label">{{ __('Cotización enviada') }}</div><div class="value">{{ $summary['cotizacion_enviada'] }}</div></td>
                <td><div class="label">{{ __('Pendiente respuesta') }}</div><div class="value">{{ $summary['pendiente_respuesta'] }}</div></td>
                <td><div class="label">{{ __('Ganadas') }}</div><div class="value">{{ $summary['won'] }}</div></td>
                <td><div class="label">{{ __('Perdidas') }}</div><div class="value">{{ $summary['lost'] }}</div></td>
            </tr>
            <tr>
                <td><div class="label">{{ __('Conversión') }}</div><div class="value">{{ $summary['conversion'] !== null ? $summary['conversion'].'%' : __('Sin datos') }}</div></td>
                <td><div class="label">{{ __('Tiempo prom. atención') }}</div><div class="value">{{ $summary['avg_attention_minutes'] !== null ? $summary['avg_attention_minutes'].' min' : __('Sin datos') }}</div></td>
                <td><div class="label">{{ __('Sin seguimiento') }}</div><div class="value">{{ $summary['without_follow_up'] }}</div></td>
                <td></td>
            </tr>
        </table>

        <h2 class="section">{{ __('Por vendedora') }}</h2>
        @if ($byVendedora->isEmpty())
            <p class="note">{{ __('Sin asignaciones registradas.') }}</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>{{ __('Vendedora') }}</th>
                        <th>{{ __('Estatus') }}</th>
                        <th>{{ __('Asig.') }}</th>
                        <th>{{ __('Atend.') }}</th>
                        <th>{{ __('Sin seg.') }}</th>
                        <th>{{ __('En seg.') }}</th>
                        <th>{{ __('Cot. env.') }}</th>
                        <th>{{ __('Pend.') }}</th>
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
                            <td>{{ $row['is_active'] ? __('Activa') : __('Inactiva') }}</td>
                            <td>{{ $row['assigned'] }}</td>
                            <td>{{ $row['attended'] }}</td>
                            <td>{{ $row['without_follow_up'] }}</td>
                            <td>{{ $row['en_seguimiento'] }}</td>
                            <td>{{ $row['cotizacion_enviada'] }}</td>
                            <td>{{ $row['pendiente_respuesta'] }}</td>
                            <td>{{ $row['won'] }}</td>
                            <td>{{ $row['lost'] }}</td>
                            <td>{{ $row['conversion'] !== null ? $row['conversion'].'%' : '—' }}</td>
                            <td>{{ $row['avg_attention_minutes'] !== null ? $row['avg_attention_minutes'].' min' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h2 class="section">{{ __('Por estado') }}</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>{{ __('Estado') }}</th>
                    <th>{{ __('Cantidad') }}</th>
                    <th>{{ __('% del total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($byStatus as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['count'] }}</td>
                        <td>{{ $row['percentage'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @php
            $topVendedora = $byVendedora->sortByDesc('won')->first();
            $topWithoutFollowUp = $byVendedora->sortByDesc('without_follow_up')->first();
            $topStatus = $byStatus->sortByDesc('count')->first();
        @endphp

        <h2 class="section">{{ __('Observaciones automáticas') }}</h2>
        <div class="observations">
            <ul>
                <li>
                    {{ __('Mejor vendedora por ganadas') }}:
                    {{ $topVendedora && $topVendedora['won'] > 0 ? $topVendedora['name'].' ('.$topVendedora['won'].')' : __('Sin datos') }}
                </li>
                <li>
                    {{ __('Mayor número de cotizaciones sin seguimiento') }}:
                    {{ $topWithoutFollowUp && $topWithoutFollowUp['without_follow_up'] > 0 ? $topWithoutFollowUp['name'].' ('.$topWithoutFollowUp['without_follow_up'].')' : __('Sin datos') }}
                </li>
                <li>
                    {{ __('Estado dominante del pipeline') }}:
                    {{ $topStatus && $topStatus['count'] > 0 ? $topStatus['label'].' ('.$topStatus['count'].')' : __('Sin datos') }}
                </li>
            </ul>
        </div>
    @endif

    <div class="footer">
        {{ __('Documento generado automáticamente por el CRM Cotizaciones Concreplus.') }}
    </div>

</body>
</html>
