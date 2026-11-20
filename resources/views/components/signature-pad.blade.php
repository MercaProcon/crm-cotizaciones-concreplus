@props(['name', 'label'])

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>

    <div class="border border-gray-300 rounded-md bg-white">
        <canvas id="{{ $name }}_canvas" class="w-full touch-none cursor-crosshair block" style="height: 180px;"></canvas>
    </div>

    <div class="mt-2 flex items-center gap-3">
        <button type="button" id="{{ $name }}_clear"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-50">
            {{ __('Limpiar firma') }}
        </button>
        <span id="{{ $name }}_status" class="text-xs text-gray-400">{{ __('Sin firmar') }}</span>
    </div>

    <input type="hidden" name="{{ $name }}_data" id="{{ $name }}_data">
    <input type="hidden" name="{{ $name }}_touched" id="{{ $name }}_touched" value="">

    @error($name.'_touched')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @error($name.'_data')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<script>
(function () {
    const canvas = document.getElementById('{{ $name }}_canvas');
    const clearBtn = document.getElementById('{{ $name }}_clear');
    const statusEl = document.getElementById('{{ $name }}_status');
    const dataInput = document.getElementById('{{ $name }}_data');
    const touchedInput = document.getElementById('{{ $name }}_touched');
    const ctx = canvas.getContext('2d');

    let drawing = false;
    let hasDrawn = false;

    function resizeCanvas() {
        const ratio = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#007856';
    }

    resizeCanvas();

    function pointerPos(event) {
        const rect = canvas.getBoundingClientRect();
        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    }

    function updateHiddenFields() {
        dataInput.value = canvas.toDataURL('image/png');
        touchedInput.value = hasDrawn ? '1' : '';
        statusEl.textContent = hasDrawn ? '{{ __('Firmado') }}' : '{{ __('Sin firmar') }}';
    }

    canvas.addEventListener('pointerdown', function (event) {
        drawing = true;
        hasDrawn = true;
        const pos = pointerPos(event);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        canvas.setPointerCapture(event.pointerId);
        event.preventDefault();
    });

    canvas.addEventListener('pointermove', function (event) {
        if (!drawing) return;
        const pos = pointerPos(event);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        event.preventDefault();
    });

    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (evt) {
        canvas.addEventListener(evt, function () {
            if (!drawing) return;
            drawing = false;
            updateHiddenFields();
        });
    });

    clearBtn.addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasDrawn = false;
        dataInput.value = '';
        touchedInput.value = '';
        statusEl.textContent = '{{ __('Sin firmar') }}';
    });
})();
</script>
