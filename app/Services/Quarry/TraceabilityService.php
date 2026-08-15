<?php

namespace App\Services\Quarry;

use App\Models\BlastPlan;
use App\Models\DispatchOrder;
use App\Models\LabSample;
use App\Models\ProductionRun;

/**
 * One-click traceability: Blast -> Quarry Face -> Production -> Sample ->
 * Laboratory Tests -> Quality Approval -> Stockpile -> Dispatch -> Customer.
 *
 * Deliberately read-only, denormalized-string lookups (matching batch_number/
 * production_number/blast_number the way DispatchOrder already stores driver
 * info as plain strings) rather than new FKs on ProductionRun/BlastPlan/
 * QuarryFace — those stable, already-shipped flagships stay untouched.
 */
class TraceabilityService
{
    public function traceSample(LabSample $sample): array
    {
        $blastPlan = $sample->blast_number
            ? BlastPlan::withoutGlobalScope('company')->where('blast_number', $sample->blast_number)->first()
            : null;

        $productionRun = $sample->production_number
            ? ProductionRun::withoutGlobalScope('company')->where('run_number', $sample->production_number)->first()
            : null;

        $dispatchOrder = $sample->stockpile_id
            ? DispatchOrder::withoutGlobalScope('company')
                ->where('stockpile_id', $sample->stockpile_id)
                ->when($sample->material_id, fn ($q) => $q->where('material_id', $sample->material_id))
                ->latest('created_at')
                ->first()
            : null;

        return [
            [
                'stage' => 'Blast',
                'found' => (bool) $blastPlan,
                'label' => $blastPlan?->blast_number ?? $sample->blast_number ?? 'Not recorded',
                'record' => $blastPlan,
            ],
            [
                'stage' => 'Quarry Face',
                'found' => (bool) ($blastPlan?->quarryFace ?? $sample->extractionSite),
                'label' => $blastPlan?->quarryFace?->name ?? $sample->extractionSite?->name ?? 'Not recorded',
                'record' => $blastPlan?->quarryFace,
            ],
            [
                'stage' => 'Production',
                'found' => (bool) $productionRun,
                'label' => $productionRun?->run_number ?? $sample->production_number ?? 'Not recorded',
                'record' => $productionRun,
            ],
            [
                'stage' => 'Sample',
                'found' => true,
                'label' => $sample->sample_number,
                'record' => $sample,
            ],
            [
                'stage' => 'Laboratory Tests',
                'found' => $sample->tests()->exists(),
                'label' => $sample->tests()->count() . ' test(s) — ' . $sample->evaluateResult(),
                'record' => $sample->tests,
            ],
            [
                'stage' => 'Quality Approval',
                'found' => $sample->isApprovalPending() || $sample->isApproved(),
                'label' => $sample->currentApproval()?->status ?? ($sample->isApproved() ? 'Approved' : 'Not submitted'),
                'record' => $sample->currentApproval(),
            ],
            [
                'stage' => 'Stockpile',
                'found' => (bool) $sample->stockpile,
                'label' => $sample->stockpile?->label ?? 'Not recorded',
                'record' => $sample->stockpile,
            ],
            [
                'stage' => 'Dispatch',
                'found' => (bool) $dispatchOrder,
                'label' => $dispatchOrder?->dispatch_number ?? 'Not yet dispatched',
                'record' => $dispatchOrder,
            ],
            [
                'stage' => 'Customer',
                'found' => (bool) $dispatchOrder?->customer,
                'label' => $dispatchOrder?->customer?->company_name ?? 'Not recorded',
                'record' => $dispatchOrder?->customer,
            ],
        ];
    }
}
