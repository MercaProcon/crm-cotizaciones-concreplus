@props(['seconds' => 60])

<script>
    (function () {
        const intervalMs = {{ (int) $seconds }} * 1000;

        setInterval(function () {
            const active = document.activeElement;
            const tag = active ? active.tagName : null;

            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
                return;
            }

            window.location.reload();
        }, intervalMs);
    })();
</script>
