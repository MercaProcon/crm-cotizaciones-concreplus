<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Solicitud de rotulación') }} {{ $facadeRequest->folio }} — PROCON</title>

        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100">
        <div class="bg-facades-primary">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 py-4">
                <span class="text-white text-xl font-bold tracking-wide">PROCON</span>
                <p class="text-facades-primary-light text-xs mt-0.5">{{ __('Solicitud de Rotulación de Fachadas') }}</p>
            </div>
        </div>

        <div class="py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 space-y-6">

                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h1 class="text-lg font-semibold text-gray-800">
                            {{ __('Folio') }} <span class="text-facades-primary">{{ $facadeRequest->folio }}</span>
                        </h1>
                        <x-status-badge :status="$facadeRequest->status" :label="$statusLabel"
                                         class="!bg-facades-secondary-light !text-facades-secondary-dark" />
                    </div>

                    @if (session('status'))
                        <div class="rounded-md bg-facades-secondary-light border border-facades-secondary/30 text-facades-secondary-dark text-sm p-4 mb-6">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="rounded-md bg-red-50 border border-red-200 text-red-800 text-sm p-4 mb-6">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($facadeRequest->status === 'autorizada')
                        <div class="rounded-md bg-facades-secondary-light border border-facades-secondary/30 text-sm text-gray-700 p-4 mb-6">
                            <p class="font-semibold text-facades-secondary-dark">
                                {{ __('Esta solicitud ya fue autorizada el') }} {{ $facadeRequest->authorized_at?->format('d/m/Y H:i') }}.
                            </p>
                            @if ($facadeRequest->authorization_comment)
                                <p class="text-gray-600 mt-1">{{ __('Comentario') }}: {{ $facadeRequest->authorization_comment }}</p>
                            @endif
                        </div>
                    @elseif ($facadeRequest->status === 'rechazada')
                        <div class="rounded-md bg-red-50 border border-red-200 text-sm text-gray-700 p-4 mb-6">
                            <p class="font-semibold text-red-800">
                                {{ __('Esta solicitud ya fue rechazada el') }} {{ $facadeRequest->rejected_at?->format('d/m/Y H:i') }}.
                            </p>
                            <p class="text-gray-600 mt-1">{{ __('Motivo') }}: {{ $facadeRequest->authorization_comment }}</p>
                        </div>
                    @elseif ($canDecide)
                        <div class="rounded-md bg-facades-primary-light border border-facades-primary/20 text-sm text-gray-700 p-4 mb-6">
                            {{ __('Estás revisando esta solicitud como autorizador. Puedes autorizarla o rechazarla al final de esta página.') }}
                        </div>
                    @else
                        <div class="rounded-md bg-gray-50 border border-gray-200 text-sm text-gray-700 p-4 mb-6">
                            {{ __('Esta solicitud ya no está disponible para autorización.') }}
                        </div>
                    @endif

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
                    </dl>

                    <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-2 pb-2 border-b-2 border-facades-secondary">{{ __('Ubicación') }}</h3>
                    <dl class="grid grid-cols-1 gap-y-4 text-sm mb-6">
                        <div>
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
                        <div>
                            <dt class="font-medium text-gray-500">{{ __('Google Maps') }}</dt>
                            <dd class="text-gray-900">
                                <a href="{{ $facadeRequest->google_maps_url }}" target="_blank" rel="noopener" class="text-facades-primary hover:text-facades-primary-dark break-all">
                                    {{ $facadeRequest->google_maps_url }}
                                </a>
                            </dd>
                        </div>
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
                    </dl>

                    <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-2 pb-2 border-b-2 border-facades-secondary">{{ __('Autorización') }}</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm mb-6">
                        <div>
                            <dt class="font-medium text-gray-500">{{ __('Autorizador') }}</dt>
                            <dd class="text-gray-900">{{ $facadeRequest->authorizer_name }}</dd>
                        </div>
                    </dl>

                    <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-2 pb-2 border-b-2 border-facades-secondary">{{ __('Firmas') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-xs font-medium text-gray-500 mb-1">{{ __('Firma del cliente') }}</p>
                            @if ($clientSignatureDataUri)
                                <img src="{{ $clientSignatureDataUri }}" alt="{{ __('Firma del cliente') }}"
                                     class="border border-gray-200 rounded-md bg-white max-w-full h-32 object-contain">
                                <p class="text-sm text-gray-700 mt-1">{{ $facadeRequest->client_signature_name }}</p>
                            @else
                                <p class="text-sm text-gray-400">{{ __('Sin firma') }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 mb-1">{{ __('Firma del agente') }}</p>
                            @if ($agentSignatureDataUri)
                                <img src="{{ $agentSignatureDataUri }}" alt="{{ __('Firma del agente') }}"
                                     class="border border-gray-200 rounded-md bg-white max-w-full h-32 object-contain">
                                <p class="text-sm text-gray-700 mt-1">{{ $facadeRequest->agent_signature_name }}</p>
                            @else
                                <p class="text-sm text-gray-400">{{ __('Sin firma') }}</p>
                            @endif
                        </div>
                    </div>

                    <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-2 pb-2 border-b-2 border-facades-secondary">{{ __('Fotos') }}</h3>
                    @if (empty($photos))
                        <p class="text-sm text-gray-500">{{ __('No hay fotos guardadas.') }}</p>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                            @foreach ($photos as $photo)
                                <div class="rounded-md overflow-hidden border border-gray-200 aspect-square">
                                    <img src="{{ $photo['data_uri'] }}" alt="{{ $photo['original_name'] }}" class="w-full h-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="border-t border-gray-200 pt-4 mb-6">
                        <a href="{{ route('facades.authorization.pdf', $facadeRequest->authorization_token) }}"
                           class="inline-flex items-center px-3 py-1.5 border border-facades-primary rounded-md text-xs font-medium text-facades-primary hover:bg-facades-primary-light">
                            {{ __('Descargar PDF') }}
                        </a>
                    </div>

                    @if ($canDecide)
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-4">{{ __('Tu decisión') }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <form method="POST" action="{{ route('facades.authorization.approve', $facadeRequest->authorization_token) }}">
                                    @csrf
                                    <label for="approve_comment" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Comentario (opcional)') }}</label>
                                    <textarea name="approve_comment" id="approve_comment" rows="3"
                                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm mb-3">{{ old('approve_comment') }}</textarea>
                                    @error('approve_comment')
                                        <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
                                    @enderror
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-facades-secondary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-facades-secondary-dark">
                                        {{ __('Autorizar solicitud') }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('facades.authorization.reject', $facadeRequest->authorization_token) }}">
                                    @csrf
                                    <label for="reject_comment" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Motivo del rechazo') }} *</label>
                                    <textarea name="reject_comment" id="reject_comment" rows="3" required
                                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm mb-3">{{ old('reject_comment') }}</textarea>
                                    @error('reject_comment')
                                        <p class="text-sm text-red-600 mb-3">{{ $message }}</p>
                                    @enderror
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                        {{ __('Rechazar solicitud') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </body>
</html>
