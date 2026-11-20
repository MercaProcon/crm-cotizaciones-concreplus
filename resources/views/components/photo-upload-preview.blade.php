@props(['name' => 'photos', 'label' => null, 'min' => 2, 'max' => 8])

<div>
    @if ($label)
        <label for="{{ $name }}_picker" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
    @endif

    {{-- Input visible: solo abre el selector de archivos, no se envía (sin "name"). --}}
    <input type="file" id="{{ $name }}_picker" multiple
           accept="image/jpeg,image/png,image/webp"
           class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-facades-secondary-light file:text-facades-secondary hover:file:bg-facades-secondary file:cursor-pointer hover:file:text-white">

    {{-- Input real: acumula la selección completa entre varias tandas y es el que se envía al servidor. --}}
    <input type="file" id="{{ $name }}_input" name="{{ $name }}[]" multiple class="hidden">

    <p class="mt-1 text-xs text-gray-500">{{ __('Formatos: jpg, jpeg, png, webp. Máximo 5 MB por foto.') }}</p>

    <p id="{{ $name }}_status" class="mt-2 text-xs text-gray-500">{{ __('0 fotos seleccionadas') }}</p>
    <p id="{{ $name }}_warning" class="mt-1 text-sm text-amber-600 hidden"></p>

    <div id="{{ $name }}_grid" class="mt-3 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3"></div>
</div>

<script>
(function () {
    const picker = document.getElementById('{{ $name }}_picker');
    const realInput = document.getElementById('{{ $name }}_input');
    const statusEl = document.getElementById('{{ $name }}_status');
    const warningEl = document.getElementById('{{ $name }}_warning');
    const grid = document.getElementById('{{ $name }}_grid');
    const min = {{ (int) $min }};
    const max = {{ (int) $max }};

    let selectedFiles = [];
    let objectUrls = [];

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function syncRealInput() {
        const dt = new DataTransfer();
        selectedFiles.forEach(function (file) { dt.items.add(file); });
        realInput.files = dt.files;
    }

    function render() {
        objectUrls.forEach(function (url) { URL.revokeObjectURL(url); });
        objectUrls = [];
        grid.innerHTML = '';

        selectedFiles.forEach(function (file, index) {
            const card = document.createElement('div');
            card.className = 'border border-gray-200 rounded-md overflow-hidden bg-white';

            const thumbWrap = document.createElement('div');
            thumbWrap.style.width = '100%';
            thumbWrap.style.height = '120px';
            thumbWrap.style.overflow = 'hidden';
            thumbWrap.style.backgroundColor = '#f9fafb';
            thumbWrap.style.display = 'flex';
            thumbWrap.style.alignItems = 'center';
            thumbWrap.style.justifyContent = 'center';

            if (file.type.startsWith('image/')) {
                const url = URL.createObjectURL(file);
                objectUrls.push(url);
                const img = document.createElement('img');
                img.src = url;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                img.style.display = 'block';
                thumbWrap.appendChild(img);
            } else {
                const placeholder = document.createElement('span');
                placeholder.className = 'text-xs text-gray-400';
                placeholder.textContent = '{{ __('Sin vista previa') }}';
                thumbWrap.appendChild(placeholder);
            }

            card.appendChild(thumbWrap);

            const info = document.createElement('div');
            info.className = 'p-1.5';

            const nameEl = document.createElement('p');
            nameEl.className = 'text-xs text-gray-700 truncate';
            nameEl.title = file.name;
            nameEl.textContent = file.name;

            const sizeEl = document.createElement('p');
            sizeEl.className = 'text-xs text-gray-400';
            sizeEl.textContent = formatSize(file.size);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'mt-1 w-full text-xs text-red-600 border border-red-200 rounded px-1 py-0.5 hover:bg-red-50';
            removeBtn.textContent = '{{ __('Quitar') }}';
            removeBtn.addEventListener('click', function () {
                selectedFiles.splice(index, 1);
                syncRealInput();
                render();
            });

            info.appendChild(nameEl);
            info.appendChild(sizeEl);
            info.appendChild(removeBtn);
            card.appendChild(info);

            grid.appendChild(card);
        });

        const count = selectedFiles.length;
        statusEl.textContent = count === 1
            ? '{{ __('1 foto seleccionada') }}'
            : count + ' {{ __('fotos seleccionadas') }}';

        if (count > 0 && count < min) {
            warningEl.textContent = '{{ __('Selecciona mínimo 2 fotografías.') }}';
            warningEl.classList.remove('hidden');
        } else if (count > max) {
            warningEl.textContent = '{{ __('Máximo 8 fotografías.') }}';
            warningEl.classList.remove('hidden');
        } else {
            warningEl.textContent = '';
            warningEl.classList.add('hidden');
        }
    }

    picker.addEventListener('change', function () {
        selectedFiles = selectedFiles.concat(Array.from(picker.files || []));
        picker.value = '';
        syncRealInput();
        render();
    });
})();
</script>
