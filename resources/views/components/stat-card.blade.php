@props([
    'title',
    'value',
    'description' => null,
    'href' => null,
    'actionLabel' => null,
    'tone' => 'neutral',
])

@php
    $toneClasses = match ($tone) {
        'warning' => 'bg-amber-50 text-amber-800',
        'danger' => 'bg-red-50 text-red-800',
        'success' => 'bg-procon-light text-procon',
        'muted' => 'bg-gray-100 text-gray-700',
        default => 'bg-procon-light text-procon',
    };
@endphp

<div {{ $attributes->merge(['class' => 'bg-white shadow-sm ring-1 ring-gray-100 rounded-xl p-5 flex flex-col justify-between']) }}>
    <div>
        <div class="text-sm font-medium text-gray-500">{{ $title }}</div>

        <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-2xl font-bold {{ $toneClasses }}">
            {{ $value }}
        </div>

        @if ($description)
            <p class="mt-2 text-xs text-gray-500">{{ $description }}</p>
        @endif
    </div>

    @if ($href && $actionLabel)
        <a href="{{ $href }}" class="mt-4 inline-flex items-center text-sm font-medium text-procon hover:text-procon-dark">
            {{ $actionLabel }}
            <span aria-hidden="true" class="ml-1">&rarr;</span>
        </a>
    @endif
</div>
