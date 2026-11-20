<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Reporte individual') }} — {{ $vendedora->name }}
            </h2>
            <a href="{{ route('reports.index') }}" class="text-sm text-procon hover:underline">&larr; {{ __('Volver a reportes') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Encabezado --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ $vendedora->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $vendedora->email }}</p>
                        <div class="mt-2">
                            @if ($vendedora->is_active)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">{{ __('Activa') }}</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">{{ __('Inactiva') }}</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('reports.vendor.pdf', array_merge(['user' => $vendedora->id], $filters)) }}"
                        class="inline-flex items-center px-4 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark focus:outline-none focus:ring-2 focus:ring-procon focus:ring-offset-2">
                        {{ __('Descargar PDF individual') }}
                    </a>
                </div>

                <form method="GET" action="{{ route('reports.vendor', $vendedora) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-100">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Fecha inicio') }}</label>
                        <input type="date" name="start_date" value="{{ $filters['start_date'] }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Fecha fin') }}</label>
                        <input type="date" name="end_date" value="{{ $filters['end_date'] }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Estado CRM') }}</label>
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <x-primary-button type="submit">{{ __('Aplicar filtros') }}</x-primary-button>
                    </div>
                </form>

                <p class="text-xs text-gray-500 pt-4">
                    @if ($filters['start_date'] || $filters['end_date'])
                        {{ __('Periodo consultado') }}: {{ $filters['start_date'] ?: __('inicio') }} — {{ $filters['end_date'] ?: __('hoy') }}
                    @else
                        {{ __('Periodo consultado') }}: {{ __('todo el histórico') }}
                    @endif
                    · {{ __('Periodo calculado con base en la fecha de registro de la cotización.') }}
                    · {{ __('Solo se muestran cotizaciones desde el :date en adelante.', ['date' => \Illuminate\Support\Carbon::parse(\App\Models\Quotation::operationalStartDate())->format('d/m/Y')]) }}
                </p>
            </div>

            @if ($metrics['assigned'] === 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">
                    {{ __('Esta vendedora no tiene cotizaciones asignadas en el periodo y filtros seleccionados.') }}
                </div>
            @else
                {{-- Tarjetas --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">{{ __('Asignadas') }}</div>
                        <div class="mt-2 text-2xl font-bold text-gray-800">{{ $metrics['assigned'] }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">{{ __('Atendidas') }}</div>
                        <div class="mt-2 text-2xl font-bold text-gray-800">{{ $metrics['attended'] }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">{{ __('Sin seguimiento') }}</div>
                        <div class="mt-2 text-2xl font-bold text-red-700">{{ $metrics['without_follow_up'] }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">{{ __('En seguimiento') }}</div>
                        <div class="mt-2 text-2xl font-bold text-gray-800">{{ $metrics['en_seguimiento'] }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">{{ __('Cotización enviada') }}</div>
                        <div class="mt-2 text-2xl font-bold text-gray-800">{{ $metrics['cotizacion_enviada'] }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">{{ __('Pendientes') }}</div>
                        <div class="mt-2 text-2xl font-bold text-gray-800">{{ $metrics['pendiente_respuesta'] }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">{{ __('Ganadas') }}</div>
                        <div class="mt-2 text-2xl font-bold text-green-700">{{ $metrics['won'] }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">{{ __('Perdidas') }}</div>
                        <div class="mt-2 text-2xl font-bold text-gray-700">{{ $metrics['lost'] }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">{{ __('Conversión') }}</div>
                        <div class="mt-2 text-2xl font-bold text-procon">
                            {{ $metrics['conversion'] !== null ? $metrics['conversion'].'%' : __('Sin datos') }}
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                        <div class="text-sm font-medium text-gray-500">{{ __('Tiempo prom. primera atención') }}</div>
                        <div class="mt-2 text-2xl font-bold text-gray-800">
                            {{ $metrics['avg_attention_minutes'] !== null ? $metrics['avg_attention_minutes'].' min' : __('Sin datos') }}
                        </div>
                    </div>
                </div>

                {{-- Tabla de cotizaciones --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <h3 class="font-semibold text-gray-800 px-6 pt-6 pb-4">{{ __('Cotizaciones asignadas') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Folio') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Cliente') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Teléfono') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Fecha registro') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Fecha asignación') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Estado CRM') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Último seguimiento') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Días sin seguimiento') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Acción') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($metrics['rows'] as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">{{ $row['quotation']->folio }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['quotation']->client_name }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['quotation']->phone_raw }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ ($row['quotation']->registered_at ?? $row['quotation']->created_at)->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['assignment']->assigned_at->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap"><x-status-badge :status="$row['quotation']->crm_status" :label="$statusLabels[$row['quotation']->crm_status]" /></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                            {{ $row['last_follow_up'] ? $row['last_follow_up']->created_at->format('d/m/Y') : __('Sin datos') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                            {{ $row['days_without_follow_up'] !== null ? $row['days_without_follow_up'] : '—' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a href="{{ route('quotations.show', $row['quotation']) }}" class="text-procon hover:underline">{{ __('Ver') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Alertas --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-6">
                    <h3 class="font-semibold text-gray-800">{{ __('Alertas') }}</h3>

                    <div>
                        <h4 class="text-sm font-semibold text-red-700 mb-2">{{ __('Cotizaciones sin seguimiento') }} ({{ $metrics['without_follow_up_rows']->count() }})</h4>
                        @if ($metrics['without_follow_up_rows']->isEmpty())
                            <p class="text-sm text-gray-500">{{ __('Sin pendientes.') }}</p>
                        @else
                            <ul class="text-sm text-gray-700 list-disc list-inside space-y-1">
                                @foreach ($metrics['without_follow_up_rows'] as $row)
                                    <li>{{ $row['quotation']->folio }} — {{ $row['quotation']->client_name }} ({{ $row['days_without_follow_up'] }} {{ __('días') }})</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-red-700 mb-2">{{ __('Más de 3 días sin seguimiento') }} ({{ $metrics['over_3_days_rows']->count() }})</h4>
                        @if ($metrics['over_3_days_rows']->isEmpty())
                            <p class="text-sm text-gray-500">{{ __('Sin pendientes.') }}</p>
                        @else
                            <ul class="text-sm text-gray-700 list-disc list-inside space-y-1">
                                @foreach ($metrics['over_3_days_rows'] as $row)
                                    <li>{{ $row['quotation']->folio }} — {{ $row['quotation']->client_name }} ({{ $row['days_without_follow_up'] }} {{ __('días') }})</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 mb-2">{{ __('Pendientes de respuesta') }} ({{ $metrics['pending_response_rows']->count() }})</h4>
                        @if ($metrics['pending_response_rows']->isEmpty())
                            <p class="text-sm text-gray-500">{{ __('Sin pendientes.') }}</p>
                        @else
                            <ul class="text-sm text-gray-700 list-disc list-inside space-y-1">
                                @foreach ($metrics['pending_response_rows'] as $row)
                                    <li>{{ $row['quotation']->folio }} — {{ $row['quotation']->client_name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-800 mb-2">{{ __('Perdidas con comentario') }} ({{ $metrics['lost_with_comment_rows']->count() }})</h4>
                        @if ($metrics['lost_with_comment_rows']->isEmpty())
                            <p class="text-sm text-gray-500">{{ __('Sin datos.') }}</p>
                        @else
                            <ul class="text-sm text-gray-700 list-disc list-inside space-y-1">
                                @foreach ($metrics['lost_with_comment_rows'] as $row)
                                    <li>{{ $row['quotation']->folio }} — {{ $row['quotation']->client_name }}: <span class="text-gray-500">{{ $row['comment'] }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
