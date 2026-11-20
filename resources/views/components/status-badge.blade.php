@props(['status', 'label' => null])

@php
    // Componente autocontenido: no depende de ningún controlador. Los que
    // llamen a este componente pueden pasar su propio texto vía :label
    // (por ejemplo, reutilizando el $statusLabels que ya calcula su
    // controlador); si no se pasa, usa este mapa de respaldo.
    $fallbackLabels = [
        'nueva' => 'Nueva',
        'asignada' => 'Asignada',
        'en_seguimiento' => 'En seguimiento',
        'cotizacion_enviada' => 'Cotización enviada',
        'pendiente_respuesta' => 'Pendiente de respuesta',
        'ganada' => 'Ganada',
        'perdida' => 'Perdida',
        'duplicada' => 'Duplicada',
        'cancelada' => 'Cancelada',
    ];

    $tones = [
        'nueva' => 'bg-blue-50 text-blue-700',
        'asignada' => 'bg-amber-50 text-amber-800',
        'en_seguimiento' => 'bg-procon-light text-procon',
        'cotizacion_enviada' => 'bg-sky-50 text-sky-700',
        'pendiente_respuesta' => 'bg-purple-50 text-purple-700',
        'ganada' => 'bg-green-100 text-green-800',
        'perdida' => 'bg-gray-100 text-gray-600',
        'duplicada' => 'bg-orange-50 text-orange-700',
        'cancelada' => 'bg-red-50 text-red-700',
    ];

    $resolvedLabel = $label ?? ($fallbackLabels[$status] ?? $status);
    $tone = $tones[$status] ?? 'bg-gray-100 text-gray-800';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {$tone}"]) }}>
    {{ $resolvedLabel }}
</span>
