<div class="tm-pill tm-clock-pill">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3" /></svg>
    <span id="tm-clock-time" class="tm-clock-time"></span>
    <span id="tm-clock-date" class="tm-clock-date"></span>
</div>
<script>
    (function () {
        function tmClockTick() {
            var timeEl = document.getElementById('tm-clock-time');
            var dateEl = document.getElementById('tm-clock-date');
            if (!timeEl || !dateEl) return;
            var d = new Date();
            timeEl.textContent = d.toLocaleTimeString(undefined, { hour12: false });
            dateEl.textContent = d.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' });
        }
        tmClockTick();
        setInterval(tmClockTick, 1000);
    })();
</script>
<?php /**PATH C:\Users\GEN X\Herd\truck-manager\resources\views/filament/partials/clock-pill.blade.php ENDPATH**/ ?>