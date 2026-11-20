@props(['title' => 'Guía rápida', 'steps' => []])

<div x-data="{ open: false }" {{ $attributes->merge(['class' => 'mb-4']) }}>
    <button type="button" @click="open = ! open"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-procon hover:text-procon-dark">
        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span x-text="open ? 'Ocultar guía rápida' : '{{ $title }}'"></span>
        <svg class="h-3 w-3 shrink-0 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open" x-transition class="mt-2 bg-procon-light rounded-lg p-4 text-sm text-gray-700 max-w-xl">
        <ol class="list-decimal list-inside space-y-1">
            @foreach ($steps as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ol>
    </div>
</div>
