<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mis cotizaciones') }}
        </h2>
    </x-slot>

    @php
        // Plantilla única del mensaje de WhatsApp (máx. 2 emojis). Se rellena
        // por cotización en cada listado (desktop y móvil) más abajo.
        $whatsappTemplate = "Hola {cliente}, buen día. Soy {vendedora} de Concreplus. 👷‍♀️\n\nGracias por contactarnos. Ya recibimos tu solicitud de cotización con folio {folio} y estamos revisando la información para prepararte una propuesta adecuada.\n\nEn breve te compartiremos tu cotización por este medio. Si requerimos algún dato adicional, te lo solicitaremos para poder atenderte mejor.\n\nQuedo atenta. ✅";
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-quick-guide :steps="[
                'Contacta al cliente por WhatsApp.',
                'Agrega seguimiento después de hablar con él.',
                'Cambia el estado según el avance.',
            ]" />

            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 text-green-800 text-sm p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('my-quotations.index') }}" class="flex flex-col sm:flex-row gap-4 sm:items-end">
                    <div>
                        <label for="crm_status" class="block text-sm font-medium text-gray-700">{{ __('Estado CRM') }}</label>
                        <select name="crm_status" id="crm_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-procon focus:ring-procon text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($crmStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark">
                            {{ __('Filtrar') }}
                        </button>
                        @if (filled($crmStatus))
                            <a href="{{ route('my-quotations.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                {{ __('Limpiar') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($quotations->isEmpty())
                    <div class="p-6 text-gray-600">
                        {{ __('No tienes cotizaciones asignadas por el momento.') }}
                    </div>
                @else
                    {{-- Desktop / tablet: tabla con columna de acciones fija a la derecha --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Cliente') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Teléfono') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Estado') }}</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">{{ __('Solicitud') }}</th>
                                    <th class="sticky right-0 z-10 bg-gray-50 px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider border-l border-gray-200">
                                        {{ __('Acciones') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($quotations as $quotation)
                                    @php
                                        $whatsappMessage = strtr($whatsappTemplate, [
                                            '{cliente}' => $quotation->client_name,
                                            '{vendedora}' => auth()->user()->name,
                                            '{folio}' => $quotation->folio,
                                        ]);
                                    @endphp
                                    <tr class="group hover:bg-gray-50">
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-medium text-gray-900">{{ $quotation->client_name }}</div>
                                            <div class="text-xs text-gray-400 mt-0.5">
                                                {{ $quotation->folio }} · {{ $quotation->registered_at->format('d/m/Y') }}
                                                @if ($quotation->quote_number)
                                                    · {{ __('Folio cotización:') }} <span class="text-gray-600 font-medium">{{ $quotation->quote_number }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 align-top">{{ $quotation->phone_raw }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap align-top">
                                            <x-status-badge :status="$quotation->crm_status" :label="$statusLabels[$quotation->crm_status] ?? null" />
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 align-top">
                                            <p class="line-clamp-2 max-w-[220px]" title="{{ $quotation->description }}">
                                                {{ $quotation->description ?? '—' }}
                                            </p>
                                        </td>
                                        <td class="sticky right-0 bg-white group-hover:bg-gray-50 px-4 py-3 align-top border-l border-gray-200">
                                            <div class="flex items-center gap-2" x-data="{ copied: false }">
                                                <a href="{{ route('quotations.show', $quotation) }}"
                                                   class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                                                    {{ __('Ver detalle') }}
                                                </a>
                                                <a href="https://wa.me/52{{ $quotation->phone_normalized }}?text={{ rawurlencode($whatsappMessage) }}" target="_blank" rel="noopener"
                                                   class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-green-300 text-xs font-medium text-green-700 hover:bg-green-50 whitespace-nowrap">
                                                    {{ __('WhatsApp') }}
                                                </a>
                                                <button type="button"
                                                        @click="navigator.clipboard.writeText('{{ $quotation->phone_normalized }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                                        class="inline-flex items-center px-2.5 py-1.5 rounded-md border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                                                    <span x-show="! copied">{{ __('Copiar') }}</span>
                                                    <span x-show="copied">{{ __('¡Copiado!') }}</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Móvil: tarjetas apiladas, sin scroll horizontal --}}
                    <div class="md:hidden divide-y divide-gray-200">
                        @foreach ($quotations as $quotation)
                            @php
                                $whatsappMessage = strtr($whatsappTemplate, [
                                    '{cliente}' => $quotation->client_name,
                                    '{vendedora}' => auth()->user()->name,
                                    '{folio}' => $quotation->folio,
                                ]);
                            @endphp
                            <div class="p-4" x-data="{ copied: false }">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="font-medium text-gray-900">{{ $quotation->client_name }}</div>
                                    <x-status-badge class="shrink-0" :status="$quotation->crm_status" :label="$statusLabels[$quotation->crm_status] ?? null" />
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    {{ $quotation->folio }} · {{ $quotation->registered_at->format('d/m/Y') }}
                                    @if ($quotation->quote_number)
                                        · {{ __('Folio cotización:') }} <span class="text-gray-600 font-medium">{{ $quotation->quote_number }}</span>
                                    @endif
                                </div>

                                <div class="mt-2 text-sm text-gray-600">
                                    {{ $quotation->phone_raw }}
                                </div>

                                <p class="mt-2 text-sm text-gray-600 line-clamp-2" title="{{ $quotation->description }}">
                                    {{ $quotation->description ?? '—' }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="{{ route('quotations.show', $quotation) }}"
                                       class="flex-1 min-w-[6rem] text-center inline-flex items-center justify-center px-2.5 py-2 rounded-md border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                        {{ __('Ver detalle') }}
                                    </a>
                                    <a href="https://wa.me/52{{ $quotation->phone_normalized }}?text={{ rawurlencode($whatsappMessage) }}" target="_blank" rel="noopener"
                                       class="flex-1 min-w-[6rem] text-center inline-flex items-center justify-center px-2.5 py-2 rounded-md border border-green-300 text-xs font-medium text-green-700 hover:bg-green-50">
                                        {{ __('WhatsApp') }}
                                    </a>
                                    <button type="button"
                                            @click="navigator.clipboard.writeText('{{ $quotation->phone_normalized }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                            class="flex-1 min-w-[6rem] text-center inline-flex items-center justify-center px-2.5 py-2 rounded-md border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                        <span x-show="! copied">{{ __('Copiar') }}</span>
                                        <span x-show="copied">{{ __('¡Copiado!') }}</span>
                                    </button>
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
