<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Fachadas') }}
            </h2>
            @can('facades.create')
                <a href="{{ route('facades.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-facades-secondary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-facades-secondary-dark">
                    {{ __('Nueva solicitud') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 text-green-800 text-sm p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($facadeRequests->isEmpty())
                    <div class="p-6 text-gray-600">
                        {{ __('No hay solicitudes de rotulación registradas todavía.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Folio') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Tienda') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Cliente') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Agente') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Fecha') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Acción') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($facadeRequests as $facadeRequest)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-facades-primary">{{ $facadeRequest->folio }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $facadeRequest->store_name }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $facadeRequest->client_name }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $facadeRequest->agent_name }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <x-status-badge :status="$facadeRequest->status" :label="$statusLabels[$facadeRequest->status] ?? null"
                                                             class="!bg-facades-secondary-light !text-facades-secondary-dark" />
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $facadeRequest->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a href="{{ route('facades.show', $facadeRequest) }}"
                                               class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                                                {{ __('Ver detalle') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-gray-200">
                        {{ $facadeRequests->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
