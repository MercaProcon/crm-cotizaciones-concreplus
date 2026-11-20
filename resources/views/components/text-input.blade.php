@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-procon focus:ring-procon rounded-md shadow-sm']) }}>
