<div class="tm-pill tm-company-badge" title="<?php echo e($companyName); ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l8-4v18M13 21V9l6 3v9M9 9h.01M9 12h.01M9 15h.01" /></svg>
    <span class="tm-company-name"><?php echo e($companyName); ?></span>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isImpersonating): ?>
        <span class="tm-impersonating-dot" title="Impersonating"></span>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\Users\GEN X\Herd\truck-manager\resources\views/filament/partials/company-badge.blade.php ENDPATH**/ ?>