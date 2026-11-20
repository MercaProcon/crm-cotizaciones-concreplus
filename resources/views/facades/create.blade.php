<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nueva solicitud de rotulación') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 text-red-800 text-sm p-4">
                    <p class="font-medium mb-1">{{ __('Revisa los siguientes campos:') }}</p>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('facades.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-4 pb-2 border-b-2 border-facades-secondary">{{ __('Agente') }}</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('Estás registrando esta solicitud como') }}
                        <span class="font-medium text-gray-900">{{ auth()->user()->name }}</span>
                        ({{ auth()->user()->email }}).
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="agent_phone" class="block text-sm font-medium text-gray-700">{{ __('Teléfono del agente') }}</label>
                            <input type="text" name="agent_phone" id="agent_phone" value="{{ old('agent_phone') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('agent_phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="zone" class="block text-sm font-medium text-gray-700">{{ __('Zona') }}</label>
                            <input type="text" name="zone" id="zone" value="{{ old('zone') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('zone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-4 pb-2 border-b-2 border-facades-secondary">{{ __('Cliente y tienda') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="store_name" class="block text-sm font-medium text-gray-700">{{ __('Nombre de la tienda') }} *</label>
                            <input type="text" name="store_name" id="store_name" value="{{ old('store_name') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('store_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="client_name" class="block text-sm font-medium text-gray-700">{{ __('Nombre del cliente') }} *</label>
                            <input type="text" name="client_name" id="client_name" value="{{ old('client_name') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('client_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="client_phone" class="block text-sm font-medium text-gray-700">{{ __('Teléfono del cliente') }} *</label>
                            <input type="text" name="client_phone" id="client_phone" value="{{ old('client_phone') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('client_phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="manager_name" class="block text-sm font-medium text-gray-700">{{ __('Nombre del encargado') }} *</label>
                            <input type="text" name="manager_name" id="manager_name" value="{{ old('manager_name') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('manager_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="manager_phone" class="block text-sm font-medium text-gray-700">{{ __('Teléfono del encargado') }} *</label>
                            <input type="text" name="manager_phone" id="manager_phone" value="{{ old('manager_phone') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('manager_phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-facade-boolean-field name="client_registered_tax" :label="__('¿Cliente registrado fiscalmente?') . ' *'" />
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-4 pb-2 border-b-2 border-facades-secondary">{{ __('Ubicación de la tienda') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="street" class="block text-sm font-medium text-gray-700">{{ __('Calle') }} *</label>
                            <input type="text" name="street" id="street" value="{{ old('street') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('street')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="number" class="block text-sm font-medium text-gray-700">{{ __('Número') }} *</label>
                            <input type="text" name="number" id="number" value="{{ old('number') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="cross_streets" class="block text-sm font-medium text-gray-700">{{ __('Entre calles') }}</label>
                            <input type="text" name="cross_streets" id="cross_streets" value="{{ old('cross_streets') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('cross_streets')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="neighborhood" class="block text-sm font-medium text-gray-700">{{ __('Colonia') }} *</label>
                            <input type="text" name="neighborhood" id="neighborhood" value="{{ old('neighborhood') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('neighborhood')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700">{{ __('Ciudad') }} *</label>
                            <input type="text" name="city" id="city" value="{{ old('city') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('city')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="municipality" class="block text-sm font-medium text-gray-700">{{ __('Municipio') }} *</label>
                            <input type="text" name="municipality" id="municipality" value="{{ old('municipality') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('municipality')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="state" class="block text-sm font-medium text-gray-700">{{ __('Estado') }} *</label>
                            <input type="text" name="state" id="state" value="{{ old('state') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('state')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700">{{ __('Código postal') }}</label>
                            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('postal_code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="google_maps_url" class="block text-sm font-medium text-gray-700">{{ __('Enlace de Google Maps') }} *</label>
                            <input type="url" name="google_maps_url" id="google_maps_url" value="{{ old('google_maps_url') }}" required
                                   placeholder="https://maps.google.com/..."
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('google_maps_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-4 pb-2 border-b-2 border-facades-secondary">{{ __('Características de la ubicación') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-facade-boolean-field name="is_corner" :label="__('¿Es esquina?') . ' *'" />
                        <x-facade-boolean-field name="is_mid_block" :label="__('¿Es media cuadra?') . ' *'" />
                        <x-facade-boolean-field name="is_end_of_street" :label="__('¿Es final de calle?') . ' *'" />
                        <x-facade-boolean-field name="near_existing_procon_facade" :label="__('¿Cerca de otra fachada PROCON?') . ' *'" />
                        <x-facade-boolean-field name="on_main_avenue" :label="__('¿Sobre avenida principal?') . ' *'" />
                        <x-facade-boolean-field name="has_clear_visibility" :label="__('¿Tiene visibilidad clara?') . ' *'" />
                    </div>
                    <div class="mt-4">
                        <label for="location_notes" class="block text-sm font-medium text-gray-700">{{ __('Notas de ubicación') }}</label>
                        <textarea name="location_notes" id="location_notes" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">{{ old('location_notes') }}</textarea>
                        @error('location_notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-4 pb-2 border-b-2 border-facades-secondary">{{ __('Relación PROCON - cliente') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="months_as_distributor" class="block text-sm font-medium text-gray-700">{{ __('Meses como distribuidor') }} *</label>
                            <input type="text" name="months_as_distributor" id="months_as_distributor" value="{{ old('months_as_distributor') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('months_as_distributor')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="estimated_monthly_consumption" class="block text-sm font-medium text-gray-700">{{ __('Consumo mensual estimado') }} *</label>
                            <input type="text" name="estimated_monthly_consumption" id="estimated_monthly_consumption" value="{{ old('estimated_monthly_consumption') }}" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            @error('estimated_monthly_consumption')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="frequent_products" class="block text-sm font-medium text-gray-700">{{ __('Productos frecuentes') }} *</label>
                            <textarea name="frequent_products" id="frequent_products" rows="2" required
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">{{ old('frequent_products') }}</textarea>
                            @error('frequent_products')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-4 pb-2 border-b-2 border-facades-secondary">{{ __('Observaciones y evidencia') }}</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="additional_comments" class="block text-sm font-medium text-gray-700">{{ __('Comentarios adicionales') }}</label>
                            <textarea name="additional_comments" id="additional_comments" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">{{ old('additional_comments') }}</textarea>
                            @error('additional_comments')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <x-photo-upload-preview name="photos" :label="__('Fotos (mínimo 2, máximo 8)') . ' *'" />
                            @error('photos')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @error('photos.*')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-4 pb-2 border-b-2 border-facades-secondary">{{ __('Autorización') }}</h3>
                    <div>
                        <label for="authorizer_key" class="block text-sm font-medium text-gray-700">{{ __('¿Quién debe autorizar esta solicitud?') }} *</label>
                        <select name="authorizer_key" id="authorizer_key" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm">
                            <option value="">{{ __('Selecciona un autorizador...') }}</option>
                            @foreach ($authorizers as $key => $authorizer)
                                <option value="{{ $key }}" {{ old('authorizer_key') === $key ? 'selected' : '' }}>
                                    {{ $authorizer['name'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('authorizer_key')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-facades-primary uppercase tracking-wider mb-4 pb-2 border-b-2 border-facades-secondary">{{ __('Firmas') }}</h3>

                    <div class="rounded-md bg-facades-primary-light border border-facades-primary/20 text-sm text-gray-700 p-4 mb-6">
                        {{ __('Al firmar, el cliente y el agente confirman que la información capturada en esta solicitud es correcta y que autorizan su envío para revisión.') }}
                    </div>

                    <div class="space-y-8">
                        <div>
                            <label for="client_signature_name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Nombre de quien firma por el cliente') }} *</label>
                            <input type="text" name="client_signature_name" id="client_signature_name" value="{{ old('client_signature_name') }}" required
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-facades-primary focus:ring-facades-primary text-sm mb-3">
                            @error('client_signature_name')
                                <p class="mt-1 text-sm text-red-600 mb-3">{{ $message }}</p>
                            @enderror
                            <x-signature-pad name="client_signature" :label="__('Firma del cliente') . ' *'" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Nombre del agente que firma') }}</label>
                            <input type="text" value="{{ auth()->user()->name }}" readonly
                                   class="block w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 shadow-sm text-sm mb-3">
                            <x-signature-pad name="agent_signature" :label="__('Firma del agente') . ' *'" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <a href="{{ route('facades.index') }}" class="text-sm text-gray-600 hover:text-gray-800">
                        {{ __('Cancelar') }}
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-facades-secondary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-facades-secondary-dark">
                        {{ __('Enviar solicitud') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
