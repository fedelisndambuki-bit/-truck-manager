<div class="relative" x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false">
    <button
        @click="open = !open"
        class="tm-pill tm-curr-trigger"
        type="button"
    >
        <span class="tm-curr-symbol"><?php echo e($currencies[$currentCurrency]['symbol']); ?></span>
        <span><?php echo e($currentCurrency); ?></span>
        <svg class="tm-chevron" :class="{ 'tm-chevron-open': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="tm-dropdown tm-curr-dropdown"
        style="display: none;"
    >
        <div class="tm-menu-label">Currency</div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $currencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button
                wire:click="setCurrency('<?php echo e($code); ?>')"
                @click="open = false"
                class="tm-menu-item <?php echo e($currentCurrency === $code ? 'tm-menu-item-sel' : ''); ?>"
                type="button"
            >
                <span class="tm-curr-symbol-box"><?php echo e($data['symbol']); ?></span>
                <span><?php echo e($data['label']); ?></span>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($currentCurrency === $code): ?>
                    <svg class="tm-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php /**PATH C:\Users\GEN X\Herd\truck-manager\resources\views/livewire/currency-switcher.blade.php ENDPATH**/ ?>