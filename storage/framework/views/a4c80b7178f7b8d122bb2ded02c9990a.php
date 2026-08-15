<?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => ['class' => 'filament-filter-section-wrapper']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'filament-filter-section-wrapper']); ?>
    <div class="filter-header">
        <div class="filter-title">
            <i class="fas fa-filter"></i>
            Filter & Reports
        </div>
        <div class="export-buttons">
            <button type="button" wire:click="export('excel')" class="btn-export excel">
                <i class="fas fa-file-excel"></i>
                Export to Excel
            </button>
            <button type="button" wire:click="export('pdf')" class="btn-export pdf">
                <i class="fas fa-file-pdf"></i>
                Export to PDF
            </button>
        </div>
    </div>
    
    <?php echo e($this->form); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $attributes = $__attributesOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $component = $__componentOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__componentOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php /**PATH C:\Users\GEN X\Herd\truck-manager\resources\views/filament/resources/invoice-resource/widgets/invoice-filter-widget.blade.php ENDPATH**/ ?>