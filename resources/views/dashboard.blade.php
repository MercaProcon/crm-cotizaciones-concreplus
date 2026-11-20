<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @php
                $user = auth()->user();
                $isAdmin = $user->hasRole('admin');
            @endphp

            @if ($variant === 'global')
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ __('Hola, :name.', ['name' => $user->name]) }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $isAdmin
                            ? __('Aquí está el estado general del CRM.')
                            : __('Estas son las cotizaciones que requieren atención.') }}
                    </p>
                </div>

                <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-5">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">{{ __('Qué hacer ahora') }}</h4>
                    <div class="flex flex-wrap gap-2">
                        @can('quotations.assign')
                            <a href="{{ route('assignments.index') }}"
                               class="inline-flex items-center px-3 py-1.5 rounded-full bg-procon-light text-procon text-sm font-medium hover:bg-procon hover:text-white transition">
                                {{ __('Ir al Panel de asignación') }}
                            </a>
                        @endcan

                        <a href="{{ route('quotations.index', ['crm_status' => 'asignada']) }}"
                           class="inline-flex items-center px-3 py-1.5 rounded-full bg-procon-light text-procon text-sm font-medium hover:bg-procon hover:text-white transition">
                            {{ __('Revisar pendientes sin atención') }}
                        </a>

                        @can('duplicates.view')
                            <a href="{{ route('duplicates.index') }}"
                               class="inline-flex items-center px-3 py-1.5 rounded-full bg-procon-light text-procon text-sm font-medium hover:bg-procon hover:text-white transition">
                                {{ __('Revisar duplicados') }}
                            </a>
                        @endcan

                        @can('reports.view')
                            <a href="{{ route('reports.index') }}"
                               class="inline-flex items-center px-3 py-1.5 rounded-full bg-procon-light text-procon text-sm font-medium hover:bg-procon hover:text-white transition">
                                {{ __('Ver reportes') }}
                            </a>
                        @endcan

                        @can('users.manage')
                            <a href="{{ route('users.index') }}"
                               class="inline-flex items-center px-3 py-1.5 rounded-full bg-procon-light text-procon text-sm font-medium hover:bg-procon hover:text-white transition">
                                {{ __('Administrar usuarios') }}
                            </a>
                        @endcan
                    </div>
                </div>

                @php
                    $cards = [
                        [
                            'title' => __('Sin asignar'),
                            'value' => $stats['unassigned'],
                            'description' => __('Nuevas cotizaciones que todavía no tienen vendedora.'),
                            'href' => route('assignments.index'),
                            'actionLabel' => __('Asignar ahora'),
                            'tone' => 'warning',
                        ],
                        [
                            'title' => __('Asignadas hoy'),
                            'value' => $stats['assigned_today'],
                            'description' => __('Cotizaciones que se asignaron el día de hoy.'),
                            'tone' => 'neutral',
                        ],
                        [
                            'title' => __('Pendientes de atención'),
                            'value' => $stats['pending'],
                            'description' => __('Asignadas, pero la vendedora aún no da seguimiento.'),
                            'href' => route('quotations.index', ['crm_status' => 'asignada']),
                            'actionLabel' => __('Ver pendientes'),
                            'tone' => 'warning',
                        ],
                        [
                            'title' => __('Más de :min min sin atender', ['min' => config('reminders.first_minutes')]),
                            'value' => $stats['over_first_minutes'],
                            'description' => __('Necesitan atención pronto.'),
                            'href' => route('quotations.index', ['crm_status' => 'asignada']),
                            'actionLabel' => __('Ver cotizaciones'),
                            'tone' => 'warning',
                        ],
                        [
                            'title' => __('Más de :min min sin atender', ['min' => config('reminders.notify_coordinator_minutes')]),
                            'value' => $stats['over_coordinator_minutes'],
                            'description' => __('Requieren intervención inmediata.'),
                            'href' => route('quotations.index', ['crm_status' => 'asignada']),
                            'actionLabel' => __('Revisar urgentes'),
                            'tone' => 'danger',
                        ],
                        [
                            'title' => __('Duplicados detectados'),
                            'value' => $stats['duplicates'],
                            'description' => __('Teléfonos con más de una solicitud registrada.'),
                            'href' => route('duplicates.index'),
                            'actionLabel' => __('Revisar duplicados'),
                            'tone' => 'warning',
                        ],
                        [
                            'title' => __('Ganadas'),
                            'value' => $stats['won'],
                            'description' => __('Cotizaciones que se convirtieron en venta.'),
                            'tone' => 'success',
                        ],
                        [
                            'title' => __('Perdidas'),
                            'value' => $stats['lost'],
                            'description' => __('Cotizaciones que no se concretaron.'),
                            'tone' => 'muted',
                        ],
                    ];
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($cards as $card)
                        <x-stat-card
                            :title="$card['title']"
                            :value="$card['value']"
                            :description="$card['description'] ?? null"
                            :href="$card['href'] ?? null"
                            :action-label="$card['actionLabel'] ?? null"
                            :tone="$card['tone']"
                        />
                    @endforeach
                </div>
            @elseif ($variant === 'vendedora')
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ __('Hola, :name.', ['name' => $user->name]) }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ __('Aquí tienes tus cotizaciones asignadas.') }}
                    </p>
                </div>

                <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-5">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">{{ __('Qué hacer ahora') }}</h4>
                    <a href="{{ route('my-quotations.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-procon text-white text-sm font-medium rounded-md hover:bg-procon-dark transition">
                        {{ __('Ver mis cotizaciones') }}
                    </a>
                    <p class="text-xs text-gray-500 mt-3">
                        {{ __('Desde ahí puedes contactar al cliente por WhatsApp, agregar seguimiento y cambiar el estado.') }}
                    </p>
                </div>

                @php
                    $cards = [
                        [
                            'title' => __('Mis cotizaciones asignadas'),
                            'value' => $stats['assigned'],
                            'description' => __('Cotizaciones que tienes que atender.'),
                            'href' => route('my-quotations.index'),
                            'actionLabel' => __('Ver mis cotizaciones'),
                            'tone' => 'neutral',
                        ],
                        [
                            'title' => __('Mis pendientes de atención'),
                            'value' => $stats['pending'],
                            'description' => __('Todavía no les has dado seguimiento.'),
                            'href' => route('my-quotations.index'),
                            'actionLabel' => __('Ver pendientes'),
                            'tone' => 'warning',
                        ],
                        [
                            'title' => __('Mías con más de :min min sin atender', ['min' => config('reminders.first_minutes')]),
                            'value' => $stats['over_first_minutes'],
                            'description' => __('Atiéndelas cuanto antes.'),
                            'href' => route('my-quotations.index'),
                            'actionLabel' => __('Ver urgentes'),
                            'tone' => 'danger',
                        ],
                        [
                            'title' => __('Mis ganadas'),
                            'value' => $stats['won'],
                            'description' => __('Cotizaciones que se convirtieron en venta.'),
                            'tone' => 'success',
                        ],
                        [
                            'title' => __('Mis perdidas'),
                            'value' => $stats['lost'],
                            'description' => __('Cotizaciones que no se concretaron.'),
                            'tone' => 'muted',
                        ],
                    ];
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($cards as $card)
                        <x-stat-card
                            :title="$card['title']"
                            :value="$card['value']"
                            :description="$card['description'] ?? null"
                            :href="$card['href'] ?? null"
                            :action-label="$card['actionLabel'] ?? null"
                            :tone="$card['tone']"
                        />
                    @endforeach
                </div>
            @else
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ __('Hola, :name.', ['name' => $user->name]) }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ __('Usa este panel para consultar clientes e historial.') }}
                    </p>
                </div>

                <div class="bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-6">
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('Tu acceso es de consulta. Puedes buscar clientes, revisar historial y detectar duplicados.') }}
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        @can('quotations.view.search')
                            <a href="{{ route('quotations.index') }}"
                               class="inline-flex items-center justify-center px-4 py-2 bg-procon text-white text-sm font-medium rounded-md hover:bg-procon-dark transition">
                                {{ __('Buscar cotización') }}
                            </a>
                        @endcan
                        @can('duplicates.view')
                            <a href="{{ route('duplicates.index') }}"
                               class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition">
                                {{ __('Revisar duplicados') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @endif
        </div>
    </div>

    <x-auto-refresh :seconds="60" />
</x-app-layout>
