@php
    $user = auth()->user();

    // Cada paso: icono (svg inline, mismo estilo que quick-guide.blade.php),
    // titulo corto y una frase orientada a "esto te lo resolvemos nosotros"
    // -- nunca una instruccion de "tienes que hacer X", el CRM quita trabajo,
    // no lo agrega.
    $roleSteps = [
        'admin' => [
            [
                'icon' => 'chart',
                'title' => __('Todo bajo control'),
                'text' => __('Ve el estado de todas las cotizaciones del equipo sin pedirle reportes a nadie.'),
            ],
            [
                'icon' => 'users',
                'title' => __('Tu equipo, organizado'),
                'text' => __('Asigna o reasigna cotizaciones entre vendedoras en un clic, desde "Asignaciones".'),
            ],
            [
                'icon' => 'mail',
                'title' => __('Reportes que llegan solos'),
                'text' => __('El reporte semanal comercial se genera y se envía automáticamente — no hay que armarlo a mano.'),
            ],
        ],
        'coordinadora' => [
            [
                'icon' => 'chart',
                'title' => __('Visibilidad total'),
                'text' => __('Ve todas las cotizaciones del equipo en un solo lugar, sin tener que preguntar.'),
            ],
            [
                'icon' => 'users',
                'title' => __('Asignar es fácil'),
                'text' => __('Reparte o reasigna cotizaciones a quien corresponda en un par de clics.'),
            ],
            [
                'icon' => 'duplicate',
                'title' => __('Duplicados detectados solos'),
                'text' => __('Si un teléfono ya tiene otra cotización, el sistema te lo señala — no tienes que revisarlo tú.'),
            ],
        ],
        'vendedora' => [
            [
                'icon' => 'bell',
                'title' => __('Te avisamos nosotros'),
                'text' => __('Cuando entra un cliente nuevo, se te asigna solo y te llega un correo al instante. No tienes que andar buscando.'),
            ],
            [
                'icon' => 'chat',
                'title' => __('Todo en un solo lugar'),
                'text' => __('En "Mis cotizaciones" están las tuyas — abres WhatsApp y registras tu seguimiento con un par de clics.'),
            ],
            [
                'icon' => 'clock',
                'title' => __('Nosotros llevamos el reloj'),
                'text' => __('Si una cotización lleva tiempo sin seguimiento, te lo recordamos solos — no tienes que acordarte tú.'),
            ],
        ],
        'ventanilla' => [
            [
                'icon' => 'search',
                'title' => __('Encuentra rápido'),
                'text' => __('Busca cualquier cotización por teléfono o nombre de cliente en segundos.'),
            ],
            [
                'icon' => 'duplicate',
                'title' => __('Duplicados a la vista'),
                'text' => __('El sistema marca solo si un teléfono ya tiene otra cotización registrada.'),
            ],
        ],
    ];

    $icons = [
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l7 7-7 7zM3 3v18h18" />',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4a4 4 0 10-4-4 4 4 0 004 4zm6 4a4 4 0 00-4-4" />',
        'mail' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />',
        'duplicate' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />',
        'bell' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />',
        'chat' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'search' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />',
    ];

    $roleKey = collect(['admin', 'coordinadora', 'vendedora', 'ventanilla'])
        ->first(fn ($role) => $user->hasRole($role)) ?? 'ventanilla';

    $steps = $roleSteps[$roleKey];
@endphp

<x-modal name="welcome-tour" :show="true" maxWidth="sm">
    <div x-data="{ step: 0, total: {{ count($steps) }} }" class="p-8 text-center">
        <img src="{{ asset('images/concreplus-logo.png') }}" alt="Concreplus" class="h-7 mx-auto mb-6">

        <div class="min-h-[160px]">
            @foreach ($steps as $i => $step)
                <div x-show="step === {{ $i }}" x-transition.opacity.duration.200ms x-cloak>
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-procon-light">
                        <svg class="h-8 w-8 text-procon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            {!! $icons[$step['icon']] !!}
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-gray-900 mb-2">
                        {{ $i === 0 ? __('¡Hola, :name!', ['name' => $user->name]) : $step['title'] }}
                    </h2>
                    <p class="text-sm text-gray-600 leading-relaxed px-2">
                        {{ $step['text'] }}
                    </p>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-center gap-1.5 mt-6 mb-5">
            @foreach ($steps as $i => $step)
                <span class="h-1.5 rounded-full transition-all"
                      :class="step === {{ $i }} ? 'w-5 bg-procon' : 'w-1.5 bg-gray-200'"></span>
            @endforeach
        </div>

        <div class="flex items-center justify-between gap-3">
            <button type="button"
                    x-show="step > 0"
                    x-on:click="step--"
                    x-cloak
                    class="text-sm text-gray-500 hover:text-gray-700">
                {{ __('Atrás') }}
            </button>
            <span x-show="step === 0" class="text-sm text-transparent select-none">{{ __('Atrás') }}</span>

            <button type="button"
                    x-show="step < total - 1"
                    x-on:click="step++"
                    class="inline-flex items-center gap-1 rounded-md bg-procon px-4 py-2 text-sm font-semibold text-white hover:bg-procon-dark">
                {{ __('Siguiente') }}
            </button>

            <form method="POST" action="{{ route('welcome-tour.complete') }}" x-show="step === total - 1" x-cloak>
                @csrf
                <button type="submit" class="inline-flex items-center gap-1 rounded-md bg-procon px-4 py-2 text-sm font-semibold text-white hover:bg-procon-dark">
                    {{ __('Entendido, comenzar') }}
                </button>
            </form>
        </div>
    </div>
</x-modal>
