<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reportes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filtros --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('reports.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Vendedora') }}</label>
                            <select name="vendedora" class="w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                                <option value="">{{ __('Todas') }}</option>
                                @foreach ($vendedoras as $vendedora)
                                    <option value="{{ $vendedora->id }}" @selected($filters['vendedora'] == $vendedora->id)>{{ $vendedora->name }}</option>
                                @endforeach
                            </select>
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
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <x-primary-button type="submit">{{ __('Aplicar filtros') }}</x-primary-button>
                        <a href="{{ route('reports.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                            {{ __('Limpiar filtros') }}
                        </a>
                        <a href="{{ route('reports.pdf', $filters) }}"
                            class="inline-flex items-center px-4 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark focus:outline-none focus:ring-2 focus:ring-procon focus:ring-offset-2">
                            {{ __('Descargar PDF general') }}
                        </a>
                        @if ($filters['vendedora'])
                            <a href="{{ route('reports.vendor', array_merge(['user' => $filters['vendedora']], $filters)) }}"
                                class="inline-flex items-center px-4 py-2 bg-white border border-procon rounded-md font-semibold text-xs text-procon uppercase tracking-widest hover:bg-procon-light focus:outline-none focus:ring-2 focus:ring-procon focus:ring-offset-2">
                                {{ __('Ver reporte individual') }}
                            </a>
                        @endif
                    </div>

                    <p class="text-xs text-gray-500 pt-1">
                        @if ($filters['start_date'] || $filters['end_date'])
                            {{ __('Periodo activo') }}: {{ $filters['start_date'] ?: __('inicio') }} — {{ $filters['end_date'] ?: __('hoy') }}
                        @else
                            {{ __('Periodo activo') }}: {{ __('todo el histórico') }}
                        @endif
                        · {{ __('Periodo calculado con base en la fecha de registro de la cotización.') }}
                        · {{ __('Solo se muestran cotizaciones desde el :date en adelante.', ['date' => \Illuminate\Support\Carbon::parse(\App\Models\Quotation::operationalStartDate())->format('d/m/Y')]) }}
                    </p>
                </form>
            </div>

            @if ($summary['total'] === 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">
                    {{ __('No hay cotizaciones que coincidan con los filtros seleccionados.') }}
                </div>
            @endif

            {{-- Resumen general --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm font-medium text-gray-500">{{ __('Total cotizaciones') }}</div>
                    <div class="mt-2 text-2xl font-bold text-gray-800">{{ $summary['total'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm font-medium text-gray-500">{{ __('Nuevas') }}</div>
                    <div class="mt-2 text-2xl font-bold text-gray-800">{{ $summary['nueva'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm font-medium text-gray-500">{{ __('Asignadas') }}</div>
                    <div class="mt-2 text-2xl font-bold text-gray-800">{{ $summary['asignada'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm font-medium text-gray-500">{{ __('En seguimiento') }}</div>
                    <div class="mt-2 text-2xl font-bold text-gray-800">{{ $summary['en_seguimiento'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm font-medium text-gray-500">{{ __('Cotización enviada') }}</div>
                    <div class="mt-2 text-2xl font-bold text-gray-800">{{ $summary['cotizacion_enviada'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm font-medium text-gray-500">{{ __('Pendiente de respuesta') }}</div>
                    <div class="mt-2 text-2xl font-bold text-gray-800">{{ $summary['pendiente_respuesta'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm font-medium text-gray-500">{{ __('Ganadas') }}</div>
                    <div class="mt-2 text-2xl font-bold text-green-700">{{ $summary['won'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm font-medium text-gray-500">{{ __('Perdidas') }}</div>
                    <div class="mt-2 text-2xl font-bold text-gray-700">{{ $summary['lost'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm font-medium text-gray-500">{{ __('Tasa de conversión') }}</div>
                    <div class="mt-2 text-2xl font-bold text-procon">
                        {{ $summary['conversion'] !== null ? $summary['conversion'].'%' : __('Sin datos') }}
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm font-medium text-gray-500">{{ __('Tiempo promedio de primera atención') }}</div>
                    <div class="mt-2 text-2xl font-bold text-gray-800">
                        {{ $summary['avg_attention_minutes'] !== null ? $summary['avg_attention_minutes'].' min' : __('Sin datos') }}
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm font-medium text-gray-500">{{ __('Sin seguimiento') }}</div>
                    <div class="mt-2 text-2xl font-bold text-red-700">{{ $summary['without_follow_up'] }}</div>
                </div>
            </div>

            {{-- Por vendedora --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <h3 class="font-semibold text-gray-800 px-6 pt-6">{{ __('Por vendedora') }}</h3>
                <p class="text-xs text-gray-500 px-6 pt-1 pb-4">
                    {{ __('Las métricas por vendedora consideran el historial de asignaciones del periodo; una cotización reasignada puede aparecer en el historial de más de una vendedora.') }}
                </p>

                @if ($byVendedora->isEmpty())
                    <div class="px-6 pb-6 text-sm text-gray-600">{{ __('Todavía no hay asignaciones registradas.') }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Vendedora') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Estatus') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Asignadas') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Atendidas') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Sin seguimiento') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('En seguimiento') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Cot. enviada') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Pend. respuesta') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Ganadas') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Perdidas') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Conversión') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Tiempo prom. atención') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Último seguimiento') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($byVendedora as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">{{ $row['name'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($row['is_active'])
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">{{ __('Activa') }}</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">{{ __('Inactiva') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['assigned'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['attended'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['without_follow_up'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['en_seguimiento'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['cotizacion_enviada'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['pendiente_respuesta'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['won'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['lost'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                            {{ $row['conversion'] !== null ? $row['conversion'].'%' : __('Sin datos') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                            {{ $row['avg_attention_minutes'] !== null ? $row['avg_attention_minutes'].' min' : __('Sin datos') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                            {{ $row['last_follow_up_at'] ? $row['last_follow_up_at']->format('d/m/Y') : __('Sin datos') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm space-x-2">
                                            <a href="{{ route('reports.vendor', array_merge(['user' => $row['id']], $filters)) }}" class="text-procon hover:underline">{{ __('Ver detalle') }}</a>
                                            <a href="{{ route('reports.vendor.pdf', array_merge(['user' => $row['id']], $filters)) }}" class="text-procon hover:underline">{{ __('PDF') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-6">
                {{-- Por estado --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg max-w-2xl">
                    <h3 class="font-semibold text-gray-800 px-6 pt-6 pb-4">{{ __('Por estado') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Cantidad') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('% del total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($byStatus as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">{{ $row['label'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['count'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $row['percentage'] }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
