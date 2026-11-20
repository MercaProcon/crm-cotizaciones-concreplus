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
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
        'mail' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />',
        'duplicate' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />',
        'bell' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />',
        'chat' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'search' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />',
    ];

    $roleKey = collect(['admin', 'coordinadora', 'vendedora', 'ventanilla'])
        ->first(fn ($role) => $user->hasRole($role)) ?? 'ventanilla';

    $steps = $roleSteps[$roleKey];
@endphp

{{--
    Animacion: entrada del modal queda con la curva propia de <x-modal> (es
    compartido con el resto del CRM, no se toca aqui). Lo que sí es nuevo:
    - Paso a paso desliza horizontalmente (no solo opacity) y con sentido
      consistente de avance (entra desde la derecha, sale por la izquierda),
      ease-in-out fuerte, 220ms.
    - El icono de cada paso aparece con un "pop" (scale 0.5->1 + opacity),
      ease-out fuerte, 320ms, atado al mismo "step" que dispara el slide.
    - Los puntos de progreso ya tenian transition-all; se deja explicito
      (ease, 150ms) por ser cambio de color/ancho, no entrada/salida.
    - Botones con active:scale-95 (feedback de press), 100ms.
    - Cierre instantaneo via fetch en vez de submit de formulario (antes
      recargaba toda la pagina).
--}}
<x-modal name="welcome-tour" :show="true" maxWidth="sm">
    <div
        x-data="{
            step: 0,
            total: {{ count($steps) }},
            dismissing: false,
            dismiss() {
                if (this.dismissing) return;
                this.dismissing = true;
                fetch('{{ route('welcome-tour.complete') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                }).finally(() => window.dispatchEvent(new CustomEvent('close-modal', { detail: 'welcome-tour' })));
            },
        }"
        class="p-8 text-center"
    >
        <img src="{{ asset('images/concreplus-logo.png') }}" alt="Concreplus" class="h-7 mx-auto mb-6">

        <div class="relative min-h-[160px] overflow-hidden">
            @foreach ($steps as $i => $step)
                <div
                    class="welcome-tour-step absolute inset-0"
                    :class="step === {{ $i }} ? 'z-10' : 'z-0'"
                    x-show="step === {{ $i }}"
                    x-cloak
                    x-transition:enter="transition ease-[cubic-bezier(0.77,0,0.175,1)] duration-[220ms]"
                    x-transition:enter-start="opacity-0 translate-x-3"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-[cubic-bezier(0.23,1,0.32,1)] duration-[90ms]"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                >
                    <template x-if="step === {{ $i }}">
                        <div class="welcome-tour-icon-pop mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-procon-light">
                            <svg class="h-8 w-8 text-procon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                {!! $icons[$step['icon']] !!}
                            </svg>
                        </div>
                    </template>
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
                <span class="h-1.5 rounded-full transition-all duration-150 ease-in-out"
                      :class="step === {{ $i }} ? 'w-5 bg-procon' : 'w-1.5 bg-gray-200'"></span>
            @endforeach
        </div>

        <div class="flex items-center justify-between gap-3">
            <button type="button"
                    x-show="step > 0"
                    x-on:click="step--"
                    x-cloak
                    class="text-sm text-gray-500 transition-transform duration-100 active:scale-95 hover:text-gray-700">
                {{ __('Atrás') }}
            </button>
            <span x-show="step === 0" class="text-sm text-transparent select-none">{{ __('Atrás') }}</span>

            <button type="button"
                    x-show="step < total - 1"
                    x-on:click="step++"
                    class="inline-flex items-center gap-1 rounded-md bg-procon px-4 py-2 text-sm font-semibold text-white transition-transform duration-100 active:scale-95 hover:bg-procon-dark">
                {{ __('Siguiente') }}
            </button>

            <button type="button"
                    x-show="step === total - 1"
                    x-cloak
                    x-on:click="dismiss()"
                    :disabled="dismissing"
                    class="inline-flex items-center gap-1 rounded-md bg-procon px-4 py-2 text-sm font-semibold text-white transition-transform duration-100 active:scale-95 hover:bg-procon-dark disabled:opacity-70">
                {{ __('Entendido, comenzar') }}
            </button>
        </div>
    </div>
</x-modal>
