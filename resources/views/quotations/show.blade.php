<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cotización') }} {{ $quotation->folio }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <a href="{{ route('quotations.index') }}" class="text-sm text-procon hover:text-procon-dark">
                        &larr; {{ __('Volver al listado') }}
                    </a>
                    <x-status-badge :status="$quotation->crm_status" :label="$statusLabels[$quotation->crm_status] ?? null" />
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Folio') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->folio }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Fecha de registro') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->registered_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Cliente') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->client_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Teléfono') }}</dt>
                        <dd class="text-gray-900">
                            {{ $quotation->phone_raw }}
                            <span class="text-gray-400">({{ $quotation->phone_normalized }})</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Ubicación') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->location_link ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-gray-500">{{ __('Solicitud') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->description ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Asignado originalmente en Sheets') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->sheet_assigned_to ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Asignado actualmente (CRM)') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->currentAssignment?->assignedTo?->name ?? __('Sin asignar') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Primera atención') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->first_attention_at?->format('d/m/Y H:i') ?? __('Sin atender todavía') }}</dd>
                    </div>
                </dl>

                @can('quotations.assign')
                    @if (! $quotation->currentAssignment)
                        <div class="mt-6 border-t border-gray-200 pt-4">
                            <h4 class="text-sm font-semibold text-gray-800 mb-2">{{ __('Asignar cotización') }}</h4>
                            <form method="POST" action="{{ route('assignments.store', $quotation) }}" class="flex flex-col sm:flex-row gap-2 sm:items-start">
                                @csrf
                                <select name="assigned_to_user_id" class="rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm" required>
                                    <option value="">{{ __('Seleccionar vendedora') }}</option>
                                    @foreach ($vendedoras as $vendedora)
                                        <option value="{{ $vendedora->id }}">{{ $vendedora->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="notes" placeholder="{{ __('Observación interna (opcional)') }}"
                                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark">
                                    {{ __('Asignar') }}
                                </button>
                            </form>
                        </div>
                    @endif
                @endcan

                @can('quotations.reassign')
                    @if ($quotation->currentAssignment)
                        <div class="mt-6 border-t border-gray-200 pt-4">
                            <h4 class="text-sm font-semibold text-gray-800 mb-2">{{ __('Reasignar cotización') }}</h4>
                            <form method="POST" action="{{ route('assignments.store', $quotation) }}" class="flex flex-col sm:flex-row gap-2 sm:items-start">
                                @csrf
                                <select name="assigned_to_user_id" class="rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm" required>
                                    <option value="">{{ __('Seleccionar vendedora') }}</option>
                                    @foreach ($vendedoras as $vendedora)
                                        <option value="{{ $vendedora->id }}">{{ $vendedora->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="notes" placeholder="{{ __('Observación interna (opcional)') }}"
                                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark">
                                    {{ __('Reasignar') }}
                                </button>
                            </form>
                        </div>
                    @endif
                @endcan
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">{{ __('Seguimiento') }}</h3>

                @if ($canAddFollowUp)
                    @php
                        $priorQuoteNumbers = $phoneHistory->pluck('quote_number')->filter()->unique()->values();
                    @endphp
                    <form method="POST" action="{{ route('follow-ups.store', $quotation) }}" class="space-y-3 mb-6">
                        @csrf
                        <div>
                            <label for="quote_number" class="block text-sm font-medium text-gray-700">{{ __('Folio de la cotización') }}</label>
                            <input type="text" name="quote_number" id="quote_number" required
                                   list="quote-numbers-cliente"
                                   value="{{ old('quote_number', $quotation->quote_number) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm"
                                   placeholder="{{ __('Ej. 4023C') }}">
                            @if ($priorQuoteNumbers->isNotEmpty())
                                <datalist id="quote-numbers-cliente">
                                    @foreach ($priorQuoteNumbers as $priorQuoteNumber)
                                        <option value="{{ $priorQuoteNumber }}"></option>
                                    @endforeach
                                </datalist>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ __('Este cliente ya tiene folio(s) previo(s): :folios. Puedes reutilizar uno si es la misma cotización.', ['folios' => $priorQuoteNumbers->implode(', ')]) }}
                                </p>
                            @endif
                        </div>
                        <div>
                            <label for="comment" class="block text-sm font-medium text-gray-700">{{ __('Comentario (opcional)') }}</label>
                            <textarea name="comment" id="comment" rows="2"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm"
                                      placeholder="{{ __('¿Qué pasó con esta cotización?') }}"></textarea>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                            <select name="crm_status" class="rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                                <option value="">{{ __('Mantener estado actual') }}</option>
                                @foreach ($followUpStatusLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark">
                                {{ __('Guardar seguimiento') }}
                            </button>
                        </div>
                    </form>
                @endif

                @if ($followUps->isEmpty())
                    <p class="text-sm text-gray-600">{{ __('Todavía no hay seguimientos registrados.') }}</p>
                @else
                    <ul class="divide-y divide-gray-200 text-sm">
                        @foreach ($followUps as $followUp)
                            <li class="py-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-900">{{ $followUp->user?->name ?? __('Usuario eliminado') }}</span>
                                    <span class="text-gray-400">{{ $followUp->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="text-gray-600">
                                    <x-status-badge :status="$followUp->status" :label="$statusLabels[$followUp->status] ?? null" />
                                </div>
                                @if ($followUp->comment)
                                    <p class="text-gray-700 mt-1">{{ $followUp->comment }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">{{ __('Datos importados de Google Sheets') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Estado original (hoja)') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->sheet_status ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('¿Cotización enviada?') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->quote_sent_status ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('No. de cotización') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->quote_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Ventas concluida') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->sale_closed ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Retorno') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->return_status ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Columna 1') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->extra_column_1 ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-gray-500">{{ __('Comentarios (hoja)') }}</dt>
                        <dd class="text-gray-900">{{ $quotation->sheet_comments ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">
                    {{ __('Historial por teléfono') }}
                    <span class="text-gray-400 font-normal">
                        ({{ $phoneHistory->count() }} {{ __('registro(s) adicionales') }})
                    </span>
                </h3>

                @if ($phoneHistory->isEmpty())
                    <p class="text-sm text-gray-600">
                        {{ __('No hay otras cotizaciones registradas con este teléfono.') }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Folio') }}</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Fecha') }}</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Estado CRM') }}</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Acción') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($phoneHistory as $related)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 whitespace-nowrap font-medium text-gray-900">{{ $related->folio }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-gray-600">{{ $related->registered_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <x-status-badge :status="$related->crm_status" :label="$statusLabels[$related->crm_status] ?? null" />
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <a href="{{ route('quotations.show', $related) }}" class="text-procon hover:text-procon-dark">{{ __('Ver detalle') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
