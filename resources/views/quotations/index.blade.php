<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cotizaciones') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-quick-guide :steps="[
                'Busca por teléfono, cliente o folio.',
                'Abre el detalle para ver todos los datos.',
                'Revisa el historial del cliente por teléfono.',
            ]" />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('quotations.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
                    <div class="lg:col-span-2">
                        <label for="q" class="block text-sm font-medium text-gray-700">{{ __('Buscar') }}</label>
                        <input type="text" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                               placeholder="{{ __('Teléfono, cliente, folio, agente...') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                    </div>

                    <div>
                        <label for="crm_status" class="block text-sm font-medium text-gray-700">{{ __('Estado CRM') }}</label>
                        <select name="crm_status" id="crm_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['crm_status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="assigned_to" class="block text-sm font-medium text-gray-700">{{ __('Vendedora asignada') }}</label>
                        <select name="assigned_to" id="assigned_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                            <option value="">{{ __('Todas') }}</option>
                            @foreach ($vendedoras as $vendedora)
                                <option value="{{ $vendedora->id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $vendedora->id)>{{ $vendedora->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2 lg:col-span-4">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark">
                            {{ __('Buscar') }}
                        </button>
                        @if ($hasActiveFilters)
                            <a href="{{ route('quotations.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                {{ __('Limpiar') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($restrictedToSearch && ! $hasActiveFilters)
                    <div class="p-6 text-gray-600">
                        {{ __('Usa el buscador de arriba para encontrar el historial de un cliente por teléfono, nombre o folio.') }}
                    </div>
                @elseif ($quotations->isEmpty())
                    <div class="p-6 text-gray-600">
                        {{ __('No se encontraron cotizaciones con esos criterios.') }}
                    </div>
                @else
                    {{-- Desktop / tablet --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Cliente') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Teléfono') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Asignado a') }}</th>
                                    <th class="sticky right-0 z-10 bg-gray-50 px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider border-l border-gray-200">
                                        {{ __('Acción') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($quotations as $quotation)
                                    <tr class="group hover:bg-gray-50">
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-medium text-gray-900">{{ $quotation->client_name }}</div>
                                            <div class="text-xs text-gray-400 mt-0.5">
                                                {{ $quotation->folio }} · {{ $quotation->registered_at->format('d/m/Y') }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 align-top">{{ $quotation->phone_raw }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap align-top">
                                            <x-status-badge :status="$quotation->crm_status" :label="$statusLabels[$quotation->crm_status] ?? null" />
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 align-top">
                                            {{ $quotation->currentAssignment?->assignedTo?->name ?? __('Sin asignar') }}
                                        </td>
                                        <td class="sticky right-0 bg-white group-hover:bg-gray-50 px-4 py-3 align-top border-l border-gray-200">
                                            <a href="{{ route('quotations.show', $quotation) }}"
                                               class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                                                {{ __('Ver detalle') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Móvil --}}
                    <div class="md:hidden divide-y divide-gray-200">
                        @foreach ($quotations as $quotation)
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="font-medium text-gray-900">{{ $quotation->client_name }}</div>
                                    <x-status-badge class="shrink-0" :status="$quotation->crm_status" :label="$statusLabels[$quotation->crm_status] ?? null" />
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    {{ $quotation->folio }} · {{ $quotation->registered_at->format('d/m/Y') }}
                                </div>

                                <div class="mt-2 text-sm text-gray-600">
                                    {{ $quotation->phone_raw }}
                                </div>

                                <div class="mt-1 text-sm text-gray-600">
                                    {{ __('Asignado a') }}: {{ $quotation->currentAssignment?->assignedTo?->name ?? __('Sin asignar') }}
                                </div>

                                <div class="mt-3">
                                    <a href="{{ route('quotations.show', $quotation) }}"
                                       class="inline-flex items-center justify-center w-full px-2.5 py-2 rounded-md border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                        {{ __('Ver detalle') }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="p-4 border-t border-gray-200">
                        {{ $quotations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
