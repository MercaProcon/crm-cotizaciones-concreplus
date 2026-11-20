<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Duplicados') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-quick-guide :steps="[
                'Revisa los teléfonos repetidos.',
                'Abre el historial de ese teléfono.',
                'Confirma si es el mismo cliente o una solicitud nueva.',
            ]" />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('duplicates.index') }}" class="flex flex-col sm:flex-row gap-4 sm:items-end">
                    <div class="flex-1">
                        <label for="q" class="block text-sm font-medium text-gray-700">{{ __('Buscar por teléfono o cliente') }}</label>
                        <input type="text" name="q" id="q" value="{{ $search }}"
                               placeholder="{{ __('Teléfono o nombre del cliente...') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark">
                            {{ __('Buscar') }}
                        </button>
                        @if ($search !== '')
                            <a href="{{ route('duplicates.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                {{ __('Limpiar') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($groups->isEmpty())
                    <div class="p-6 text-gray-600">
                        {{ $search !== '' ? __('No se encontraron teléfonos duplicados con esos criterios.') : __('No hay teléfonos duplicados por ahora.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Teléfono') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Cotizaciones') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Clientes relacionados') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Estado CRM (más reciente)') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Fecha más reciente') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Acción') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($groups as $group)
                                    @php
                                        $groupQuotations = $quotationsByPhone->get($group->phone_normalized, collect());
                                        $latest = $groupQuotations->first();
                                        $clientNames = $groupQuotations->pluck('client_name')->unique()->implode(', ');
                                    @endphp
                                    @if ($latest)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">{{ $group->phone_normalized }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $group->total }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $clientNames }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <x-status-badge :status="$latest->crm_status" :label="$statusLabels[$latest->crm_status] ?? null" />
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $latest->registered_at->format('d/m/Y H:i') }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <a href="{{ route('quotations.show', $latest) }}"
                                                   class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                                                    {{ __('Ver historial') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-gray-200">
                        {{ $groups->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
