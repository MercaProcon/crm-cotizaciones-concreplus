<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Solicitud de rotulación') }} <span class="text-facades-primary">{{ $facadeRequest->folio }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 text-green-800 text-sm p-4">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 text-red-800 text-sm p-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <a href="{{ route('facades.index') }}" class="text-sm text-facades-primary hover:text-facades-primary-dark">
                        &larr; {{ __('Volver al listado') }}
                    </a>
                    <div class="flex items-center gap-3">
                        @can('facades.authorize')
                            @if ($facadeRequest->status === 'capturada')
                                {{-- El envío es automático al crear la solicitud (ver store()); si llegamos
                                     aquí con estado "capturada", el intento automático ya falló y este es
                                     el respaldo manual para reintentarlo. --}}
                                <form method="POST" action="{{ route('facades.send-authorization', $facadeRequest) }}"
                                      onsubmit="return confirm('{{ __('¿Reintentar el envío a autorización? Se mandará un correo a') }} {{ $facadeRequest->authorizer_name }}.');">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1.5 bg-facades-secondary border border-transparent rounded-md text-xs font-medium text-white hover:bg-facades-secondary-dark">
                                        {{ __('Reintentar envío a autorización') }}
                                    </button>
                                </form>
                            @elseif ($facadeRequest->authorization_email_sent_at)
                                <span class="text-xs text-gray-500">
                                    {{ __('Enviado a autorización el') }} {{ $facadeRequest->authorization_email_sent_at->format('d/m/Y H:i') }}
                                </span>
                            @endif
                        @endcan
                        @can('facades.download_pdf')
                            <a href="{{ route('facades.pdf', $facadeRequest) }}"
                               class="inline-flex items-center px-3 py-1.5 border border-facades-primary rounded-md text-xs font-medium text-facades-primary hover:bg-facades-primary-light">
                                {{ __('Descargar PDF') }}
                            </a>
                        @endcan
                        <x-status-badge :status="$facadeRequest->status" :label="$statusLabels[$facadeRequest->status] ?? null"
                                         class="!bg-facades-secondary-light !text-facades-secondary-dark" />
                    </div>
                </div>

                <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-2 pb-2 border-b-2 border-facades-secondary">{{ __('Agente') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm mb-6">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Nombre') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->agent_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Correo') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->agent_email }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Teléfono') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->agent_phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Zona') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->zone ?? '—' }}</dd>
                    </div>
                </dl>

                <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-2 pb-2 border-b-2 border-facades-secondary">{{ __('Cliente y tienda') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm mb-6">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Tienda') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->store_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Cliente') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->client_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Teléfono cliente') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->client_phone }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Encargado') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->manager_name }} ({{ $facadeRequest->manager_phone }})</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Cliente registrado fiscalmente') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->client_registered_tax ? __('Sí') : __('No') }}</dd>
                    </div>
                </dl>

                <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-2 pb-2 border-b-2 border-facades-secondary">{{ __('Ubicación') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm mb-6">
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-gray-500">{{ __('Dirección') }}</dt>
                        <dd class="text-gray-900">
                            {{ $facadeRequest->street }} {{ $facadeRequest->number }}
                            @if ($facadeRequest->cross_streets)
                                , {{ __('entre') }} {{ $facadeRequest->cross_streets }}
                            @endif
                            , {{ $facadeRequest->neighborhood }}, {{ $facadeRequest->city }}, {{ $facadeRequest->municipality }}, {{ $facadeRequest->state }}
                            @if ($facadeRequest->postal_code)
                                CP {{ $facadeRequest->postal_code }}
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-gray-500">{{ __('Google Maps') }}</dt>
                        <dd class="text-gray-900">
                            <a href="{{ $facadeRequest->google_maps_url }}" target="_blank" rel="noopener" class="text-facades-primary hover:text-facades-primary-dark break-all">
                                {{ $facadeRequest->google_maps_url }}
                            </a>
                        </dd>
                    </div>
                </dl>

                <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-2 pb-2 border-b-2 border-facades-secondary">{{ __('Características de la ubicación') }}</h3>
                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm mb-6">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Esquina') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->is_corner ? __('Sí') : __('No') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Media cuadra') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->is_mid_block ? __('Sí') : __('No') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Final de calle') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->is_end_of_street ? __('Sí') : __('No') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Cerca de otra fachada PROCON') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->near_existing_procon_facade ? __('Sí') : __('No') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Avenida principal') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->on_main_avenue ? __('Sí') : __('No') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Visibilidad clara') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->has_clear_visibility ? __('Sí') : __('No') }}</dd>
                    </div>
                    @if ($facadeRequest->location_notes)
                        <div class="col-span-2 sm:col-span-3">
                            <dt class="font-medium text-gray-500">{{ __('Notas de ubicación') }}</dt>
                            <dd class="text-gray-900">{{ $facadeRequest->location_notes }}</dd>
                        </div>
                    @endif
                </dl>

                <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-2 pb-2 border-b-2 border-facades-secondary">{{ __('Relación PROCON - cliente') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm mb-6">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Meses como distribuidor') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->months_as_distributor }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Consumo mensual estimado') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->estimated_monthly_consumption }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-gray-500">{{ __('Productos frecuentes') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->frequent_products }}</dd>
                    </div>
                    @if ($facadeRequest->additional_comments)
                        <div class="sm:col-span-2">
                            <dt class="font-medium text-gray-500">{{ __('Comentarios adicionales') }}</dt>
                            <dd class="text-gray-900">{{ $facadeRequest->additional_comments }}</dd>
                        </div>
                    @endif
                </dl>

                <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-2 pb-2 border-b-2 border-facades-secondary">{{ __('Autorización') }}</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm mb-6">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Autorizador seleccionado') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->authorizer_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('Correo del autorizador') }}</dt>
                        <dd class="text-gray-900">{{ $facadeRequest->authorizer_email }}</dd>
                    </div>
                </dl>

                <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-2 pb-2 border-b-2 border-facades-secondary">{{ __('Firmas') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">{{ __('Firma del cliente') }}</p>
                        @if ($facadeRequest->client_signature_path)
                            <img src="{{ route('facades.signature.show', [$facadeRequest, 'client']) }}"
                                 alt="{{ __('Firma del cliente') }}"
                                 class="border border-gray-200 rounded-md bg-white max-w-full h-32 object-contain">
                            <p class="text-sm text-gray-700 mt-1">{{ $facadeRequest->client_signature_name }}</p>
                        @else
                            <p class="text-sm text-gray-400">{{ __('Sin firma') }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">{{ __('Firma del agente') }}</p>
                        @if ($facadeRequest->agent_signature_path)
                            <img src="{{ route('facades.signature.show', [$facadeRequest, 'agent']) }}"
                                 alt="{{ __('Firma del agente') }}"
                                 class="border border-gray-200 rounded-md bg-white max-w-full h-32 object-contain">
                            <p class="text-sm text-gray-700 mt-1">{{ $facadeRequest->agent_signature_name }}</p>
                        @else
                            <p class="text-sm text-gray-400">{{ __('Sin firma') }}</p>
                        @endif
                    </div>
                </div>

                <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-2 pb-2 border-b-2 border-facades-secondary">{{ __('Fotos') }}</h3>
                @if ($facadeRequest->photos->isEmpty())
                    <p class="text-sm text-gray-500">{{ __('No hay fotos guardadas.') }}</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach ($facadeRequest->photos as $photo)
                            <a href="{{ route('facades.photos.show', [$facadeRequest, $photo]) }}" target="_blank" rel="noopener"
                               class="block rounded-md overflow-hidden border border-gray-200 aspect-square">
                                <img src="{{ route('facades.photos.show', [$facadeRequest, $photo]) }}"
                                     alt="{{ $photo->original_name }}"
                                     class="w-full h-full object-cover">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
