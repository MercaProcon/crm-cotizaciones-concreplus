<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de asignación') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-quick-guide :steps="[
                'Revisa las pendientes, filtra si hace falta.',
                'Asigna cada cotización a una vendedora con el selector manual por fila.',
            ]" />

            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 text-green-800 text-sm p-4">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 text-red-800 text-sm p-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('auto_assign_result'))
                @php
                    $autoResult = session('auto_assign_result');
                    $maxVisible = 20;
                    $possibleDuplicates = collect($autoResult['skipped_possible_duplicate'] ?? []);
                    $vendorConflicts = collect($autoResult['skipped_vendor_conflict'] ?? []);
                @endphp

                @if ($possibleDuplicates->isNotEmpty())
                    <div class="rounded-md bg-amber-50 border border-amber-200 text-amber-900 text-sm p-4 space-y-3">
                        <p class="font-semibold">{{ __('🟡 Posibles duplicados reales detectados') }}</p>
                        <p>{{ __('Se detectaron posibles duplicados reales. Se asignó solo la cotización principal y las demás quedaron pendientes para revisión manual.') }}</p>
                        <ul class="space-y-2">
                            @foreach ($possibleDuplicates->take($maxVisible) as $entry)
                                <li class="border-t border-amber-200 pt-2">
                                    <a href="{{ route('quotations.show', $entry['id']) }}" class="font-medium underline hover:text-amber-700">{{ $entry['folio'] }}</a>
                                    — {{ $entry['client_name'] }} — {{ $entry['phone_raw'] }}
                                    <br>
                                    {{ __('Similar a') }}:
                                    <a href="{{ route('quotations.show', $entry['principal_id']) }}" class="underline hover:text-amber-700">{{ $entry['principal_folio'] }}</a>
                                    <br>
                                    {{ __('Motivo') }}: {{ __('misma solicitud o solicitud muy parecida') }}
                                    <br>
                                    {{ __('Acción') }}: {{ __('revisar manualmente') }}
                                </li>
                            @endforeach
                        </ul>
                        @if ($possibleDuplicates->count() > $maxVisible)
                            <p class="text-xs text-amber-700">{{ __('Hay más casos omitidos. Revisa el panel de asignaciones/duplicados.') }}</p>
                        @endif
                    </div>
                @endif

                @if ($vendorConflicts->isNotEmpty())
                    <div class="rounded-md bg-red-50 border border-red-200 text-red-900 text-sm p-4 space-y-3">
                        <p class="font-semibold">{{ __('🔴 Conflictos de asignación detectados') }}</p>
                        <p>{{ __('Este teléfono tiene historial con más de una vendedora. No se asignó automáticamente y requiere revisión manual.') }}</p>
                        <ul class="space-y-2">
                            @foreach ($vendorConflicts->take($maxVisible) as $entry)
                                <li class="border-t border-red-200 pt-2">
                                    <a href="{{ route('quotations.show', $entry['id']) }}" class="font-medium underline hover:text-red-700">{{ $entry['folio'] }}</a>
                                    — {{ $entry['client_name'] }} — {{ $entry['phone_raw'] }}
                                    <br>
                                    {{ __('Vendedoras en conflicto') }}: {{ $entry['conflict_vendedoras'] }}
                                </li>
                            @endforeach
                        </ul>
                        @if ($vendorConflicts->count() > $maxVisible)
                            <p class="text-xs text-red-700">{{ __('Hay más casos omitidos. Revisa el panel de asignaciones/duplicados.') }}</p>
                        @endif
                    </div>
                @endif
            @endif

            {{-- Filtros --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('assignments.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Buscar') }}</label>
                            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Teléfono, cliente, folio o solicitud') }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Duplicado') }}</label>
                            <select name="duplicate" class="w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                                <option value="">{{ __('Todos') }}</option>
                                <option value="yes" @selected(($filters['duplicate'] ?? '') === 'yes')>{{ __('Sí') }}</option>
                                <option value="no" @selected(($filters['duplicate'] ?? '') === 'no')>{{ __('No') }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Desde') }}</label>
                                <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Hasta') }}</label>
                                <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <x-primary-button type="submit">{{ __('Buscar') }}</x-primary-button>
                        <a href="{{ route('assignments.index') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                            {{ __('Limpiar filtros') }}
                        </a>
                    </div>
                </form>
                {{-- Asignación automática deshabilitada para Concreplus (Fase 3): pendiente
                     de definir vendedoras y reglas reales antes de diseñarla. La asignación
                     manual por fila sigue disponible más abajo. --}}
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($quotations->isEmpty())
                    <div class="p-6 text-gray-600">
                        {{ __('No hay cotizaciones nuevas por asignar en este momento.') }}
                    </div>
                @else
                    {{-- Desktop / tablet --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Cliente') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Teléfono') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Solicitud') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Duplicado') }}</th>
                                    <th class="sticky right-0 z-10 bg-gray-50 px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider border-l border-gray-200">
                                        {{ __('Asignar') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($quotations as $quotation)
                                    <tr class="group hover:bg-gray-50">
                                        <td class="px-4 py-3 align-top">
                                            <a href="{{ route('quotations.show', $quotation) }}" class="font-medium text-gray-900 hover:text-procon">
                                                {{ $quotation->client_name }}
                                            </a>
                                            <div class="text-xs text-gray-400 mt-0.5">
                                                {{ $quotation->folio }} · {{ $quotation->registered_at->format('d/m/Y') }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 align-top">{{ $quotation->phone_raw }}</td>
                                        <td class="px-4 py-3 text-gray-600 align-top">
                                            <p class="line-clamp-2 max-w-[200px]" title="{{ $quotation->description }}">
                                                {{ $quotation->description ?? '—' }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap align-top">
                                            @if (in_array($quotation->phone_normalized, $duplicatePhones, true))
                                                <a href="{{ route('quotations.show', $quotation) }}" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                    {{ __('Sí') }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">{{ __('No') }}</span>
                                            @endif
                                        </td>
                                        <td class="sticky right-0 bg-white group-hover:bg-gray-50 px-4 py-3 align-top border-l border-gray-200">
                                            @can('quotations.assign')
                                                <form method="POST" action="{{ route('assignments.store', $quotation) }}" class="flex flex-col gap-2 min-w-[10rem]">
                                                    @csrf
                                                    <select name="assigned_to_user_id" class="rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm" required>
                                                        <option value="">{{ __('Seleccionar vendedora') }}</option>
                                                        @foreach ($vendedoras as $vendedora)
                                                            <option value="{{ $vendedora->id }}">{{ $vendedora->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="inline-flex items-center justify-center px-3 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark">
                                                        {{ __('Asignar') }}
                                                    </button>
                                                </form>
                                            @endcan
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
                                    <a href="{{ route('quotations.show', $quotation) }}" class="font-medium text-gray-900 hover:text-procon">
                                        {{ $quotation->client_name }}
                                    </a>
                                    @if (in_array($quotation->phone_normalized, $duplicatePhones, true))
                                        <span class="shrink-0 inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            {{ __('Duplicado') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    {{ $quotation->folio }} · {{ $quotation->registered_at->format('d/m/Y') }}
                                </div>

                                <div class="mt-2 text-sm text-gray-600">
                                    {{ $quotation->phone_raw }}
                                </div>

                                <p class="mt-2 text-sm text-gray-600 line-clamp-2" title="{{ $quotation->description }}">
                                    {{ $quotation->description ?? '—' }}
                                </p>

                                @can('quotations.assign')
                                    <form method="POST" action="{{ route('assignments.store', $quotation) }}" class="mt-3 flex flex-col gap-2">
                                        @csrf
                                        <select name="assigned_to_user_id" class="rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm" required>
                                            <option value="">{{ __('Seleccionar vendedora') }}</option>
                                            @foreach ($vendedoras as $vendedora)
                                                <option value="{{ $vendedora->id }}">{{ $vendedora->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="inline-flex items-center justify-center px-3 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark">
                                            {{ __('Asignar') }}
                                        </button>
                                    </form>
                                @endcan
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

    <x-auto-refresh :seconds="60" />
</x-app-layout>
