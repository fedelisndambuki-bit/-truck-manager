<?php use App\Filament\Resources\TripResource; ?>
<?php if (isset($component)) { $__componentOriginal166a02a7c5ef5a9331faf66fa665c256 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.page.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('heading', null, []); ?>  <?php $__env->endSlot(); ?>
     <?php $__env->slot('subheading', null, []); ?>  <?php $__env->endSlot(); ?>
     <?php $__env->slot('header', null, []); ?>  <?php $__env->endSlot(); ?>

    <style>
        .page-header-custom{background-color:#2f4f4f;padding:25px 35px;color:#fff;border-radius:10px;margin-bottom:20px}
        .page-header-content{display:flex;align-items:center;gap:12px}
        .page-header-icon{background:rgba(255,255,255,.15);padding:10px;border-radius:8px;display:flex;align-items:center;justify-content:center}
        .page-header-custom h1{font-size:24px;font-weight:600;margin-bottom:4px}
        .page-header-custom p{font-size:13px;opacity:.85;font-weight:400}
        .table-section{background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.08);overflow:hidden;margin-top:20px}
        .table-header-custom{background-color:#2f4f4f;color:#f9fafb;padding:15px 25px;display:flex;justify-content:space-between;align-items:center;border-radius:10px 10px 0 0}
        .table-title{font-size:15px;font-weight:600;color:#f9fafb;display:flex;align-items:center;gap:8px}
        .table-results{font-size:13px;color:rgba(249,250,251,.9)}
        .table-results strong{color:#fff}
        table{width:100%;border-collapse:collapse}
        thead{background:#f9fafb}
        thead th{padding:14px 20px;text-align:left;font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e5e7eb}
        tbody tr{border-bottom:1px solid #f3f4f6;transition:background-color .15s}
        tbody tr:hover{background-color:#f9fafb}
        tbody td{padding:16px 20px;font-size:13px;color:#374151;vertical-align:top}
        .route{font-size:12px;color:#6b7280}
        .badge{display:inline-block;padding:5px 12px;border-radius:12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
        .badge.scheduled{background:#e5e7eb;color:#374151}
        .badge.in-progress{background:#fef3c7;color:#92400e}
        .badge.completed{background:#d1fae5;color:#065f46}
        .badge.cancelled{background:#fee2e2;color:#991b1b}
        .actions{display:flex;gap:8px}
        .action-btn{width:32px;height:32px;border-radius:6px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;font-size:14px}
        .action-btn.edit{background:rgba(16,185,129,.1);color:#10b981}
        .action-btn:hover{transform:translateY(-2px);box-shadow:0 2px 4px rgba(0,0,0,.1)}
    </style>

    <div class="page-header-custom">
        <div class="page-header-content">
            <div class="page-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 4.5l19.5 0M3.75 9h16.5M6 13.5h12M8.25 18h7.5" />
                </svg>
            </div>
            <div>
                <h1>Trips</h1>
                <p>Monitor scheduled, in-progress and completed trips</p>
            </div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header-custom">
            <div class="table-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v12H4a1 1 0 01-1-1V4z"/></svg>
                Trip List
            </div>
            <div class="table-results">
                <?php
                    $firstItem = $this->getTable()->getRecords()->firstItem();
                    $lastItem = $this->getTable()->getRecords()->lastItem();
                    $total = $this->getTable()->getRecords()->total();
                ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($firstItem && $lastItem && $total): ?>
                    Showing <strong><?php echo e($firstItem); ?>-<?php echo e($lastItem); ?></strong> of <strong><?php echo e($total); ?></strong> results
                <?php else: ?>
                    No results
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Schedule Status</th>
                    <th>Truck</th>
                    <th>Client</th>
                    <th>Route</th>
                    <th>Departure</th>
                    <th>Expected Arrival</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->getTable()->getRecords(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $statusLabel = $trip->status instanceof \App\Enums\TripStatus ? $trip->status->value : ($trip->status ?? 'Scheduled');
                    $statusClass = strtolower(str_replace(' ', '-', $statusLabel));
                    
                    $assignmentStatus = optional($trip->assignment)->status ?? 'pending';
                    $assignmentClass = match($assignmentStatus) {
                        'accepted' => 'completed',
                        'declined' => 'cancelled',
                        default => 'scheduled',
                    };
                ?>
                <tr>
                    <td><span class="badge <?php echo e($statusClass); ?>"><?php echo e(strtoupper($statusLabel)); ?></span></td>
                    <td><span class="badge <?php echo e($assignmentClass); ?>"><?php echo e(strtoupper($assignmentStatus)); ?></span></td>
                    <td><?php echo e(optional($trip->truck)->plate_number); ?></td>
                    <td><?php echo e(optional($trip->client)->company_name); ?></td>
                    <td>
                        <div><?php echo e($trip->route_name); ?></div>
                        <div class="route"><?php echo e($trip->origin); ?> → <?php echo e($trip->destination); ?></div>
                    </td>
                    <td><?php echo e(optional($trip->departure_time)->format('M d, Y H:i')); ?></td>
                    <td><?php echo e(optional($trip->expected_arrival)->format('M d, Y H:i')); ?></td>
                    <td>
                        <div class="actions">
                            <a class="action-btn edit" title="Edit" href="<?php echo e(TripResource::getUrl('edit', ['record' => $trip->id])); ?>">✎</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:#9ca3af;">No trips found</td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->getTable()->getRecords()->hasPages()): ?>
        <div style="padding:20px 25px;border-top:1px solid #f3f4f6;">
            <?php echo e($this->getTable()->getRecords()->links()); ?>

        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php /**PATH C:\Users\GEN X\Herd\truck-manager\resources\views/filament/resources/trip-resource/pages/list-trips.blade.php ENDPATH**/ ?>