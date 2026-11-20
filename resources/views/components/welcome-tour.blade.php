@php
    $user = auth()->user();

    $roleContent = [
        'admin' => [
            'intro' => __('Tienes acceso completo al CRM de Concreplus.'),
            'points' => [
                __('Gestiona usuarios y sus roles en la sección "Usuarios".'),
                __('Ve todas las cotizaciones, asígnalas o reasígnalas entre vendedoras.'),
                __('Consulta reportes comerciales y revisa duplicados por teléfono.'),
            ],
        ],
        'coordinadora' => [
            'intro' => __('Coordinas la asignación y seguimiento de las cotizaciones del equipo.'),
            'points' => [
                __('Ve todas las cotizaciones y asígnalas a la vendedora correspondiente.'),
                __('Revisa duplicados por teléfono antes de asignar.'),
                __('Consulta los reportes comerciales para dar seguimiento al equipo.'),
            ],
        ],
        'vendedora' => [
            'intro' => __('Aquí verás y atenderás las cotizaciones que se te asignen.'),
            'points' => [
                __('En "Mis cotizaciones" están las que tienes asignadas — ahí registras tu seguimiento.'),
                __('Recibirás un correo cada vez que se te asigne una cotización nueva.'),
                __('Si una cotización lleva tiempo sin seguimiento, te llegará un recordatorio automático.'),
            ],
        ],
        'ventanilla' => [
            'intro' => __('Tienes acceso de consulta al CRM.'),
            'points' => [
                __('Puedes buscar cotizaciones por teléfono o nombre de cliente.'),
                __('Puedes revisar los duplicados detectados por teléfono.'),
            ],
        ],
    ];

    $roleKey = collect(['admin', 'coordinadora', 'vendedora', 'ventanilla'])
        ->first(fn ($role) => $user->hasRole($role)) ?? 'ventanilla';

    $content = $roleContent[$roleKey];
@endphp

<x-modal name="welcome-tour" :show="true" maxWidth="md">
    <div class="p-6">
        <div class="flex items-center gap-3 mb-4">
            <img src="{{ asset('images/concreplus-logo.png') }}" alt="Concreplus" class="h-8">
        </div>

        <h2 class="text-lg font-semibold text-gray-900 mb-1">
            {{ __('¡Bienvenido/a, :name!', ['name' => $user->name]) }}
        </h2>
        <p class="text-sm text-gray-600 mb-4">
            {{ $content['intro'] }}
        </p>

        <ul class="space-y-2 text-sm text-gray-700 mb-6">
            @foreach ($content['points'] as $point)
                <li class="flex gap-2">
                    <span class="text-procon font-bold shrink-0">&check;</span>
                    <span>{{ $point }}</span>
                </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('welcome-tour.complete') }}">
            @csrf
            <x-primary-button type="submit">
                {{ __('Entendido, comenzar') }}
            </x-primary-button>
        </form>
    </div>
</x-modal>
