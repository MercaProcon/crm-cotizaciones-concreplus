@props(['name', 'label'])

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <div class="flex gap-4">
        <label class="inline-flex items-center gap-1 text-sm text-gray-700">
            <input type="radio" name="{{ $name }}" value="1" required
                   class="text-facades-primary focus:ring-facades-primary"
                   {{ old($name) === '1' ? 'checked' : '' }}>
            {{ __('Sí') }}
        </label>
        <label class="inline-flex items-center gap-1 text-sm text-gray-700">
            <input type="radio" name="{{ $name }}" value="0" required
                   class="text-facades-primary focus:ring-facades-primary"
                   {{ old($name) === '0' ? 'checked' : '' }}>
            {{ __('No') }}
        </label>
    </div>
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
