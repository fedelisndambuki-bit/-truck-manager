<?php

namespace App\Observers;

use App\Models\ProductionLog;
use App\Models\ProductionRun;
use App\Models\SamplingRequest;

/**
 * Auto-creates a SamplingRequest (trigger 'Production Completed') whenever a
 * ProductionRun or ProductionLog is approved — the real, unambiguous "stock
 * was credited" event both models already use internally (wasChanged +
 * status === 'Approved') to fire applyToStockpiles(). Zero edits to either
 * model file; this observer is the only new wiring.
 */
class ProductionApprovalObserver
{
    public function updated(ProductionRun|ProductionLog $model): void
    {
        if (! ($model->wasChanged('status') && $model->status === 'Approved')) {
            return;
        }

        SamplingRequest::create([
            'company_id' => $model->company_id,
            'trigger' => 'Production Completed',
            'priority' => 'Normal',
            'due_date' => now()->addDay(),
            'notes' => $model instanceof ProductionRun
                ? "Auto-generated for approved Production Run {$model->run_number}"
                : "Auto-generated for approved Production Log #{$model->id}",
        ]);
    }
}
