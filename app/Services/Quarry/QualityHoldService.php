<?php

namespace App\Services\Quarry;

use App\Models\QualityHold;
use App\Models\Stockpile;
use App\Models\StockMovement;

/**
 * The single audited entry point for placing/releasing a quality hold on a
 * Stockpile — wires the dormant Stockpile::QUALITY_STATUSES 'On Hold' value
 * and StockMovement::MOVEMENT_TYPES 'Quality Hold'/'Quality Release' values
 * (both already existed in the schema but were never actually used anywhere
 * in the codebase before this module) into real, ledgered behavior.
 */
class QualityHoldService
{
    public function place(Stockpile $stockpile, string $reason, ?int $heldById = null, ?int $labSampleId = null, ?string $notes = null): QualityHold
    {
        $hold = QualityHold::create([
            'company_id' => $stockpile->company_id,
            'stockpile_id' => $stockpile->id,
            'lab_sample_id' => $labSampleId,
            'material_id' => $stockpile->material_id,
            'reason' => $reason,
            'held_by_id' => $heldById,
            'held_at' => now(),
            'status' => 'Active',
            'notes' => $notes,
        ]);

        $stockpile->update(['quality_status' => 'On Hold']);

        StockMovement::record([
            'company_id' => $stockpile->company_id,
            'movement_type' => 'Quality Hold',
            'material_id' => $stockpile->material_id,
            'quantity' => $stockpile->quantity_available,
            'source_stockpile_id' => $stockpile->id,
            'operator_id' => $heldById,
        ], $hold);

        return $hold;
    }

    public function release(QualityHold $hold, string $releaseReason, ?int $releasedById = null, ?string $notes = null): void
    {
        $hold->update([
            'status' => 'Released',
            'release_reason' => $releaseReason,
            'released_by_id' => $releasedById,
            'released_at' => now(),
            'notes' => $notes ?? $hold->notes,
        ]);

        $stockpile = $hold->stockpile;

        if ($stockpile) {
            $stockpile->update(['quality_status' => 'Approved']);

            StockMovement::record([
                'company_id' => $stockpile->company_id,
                'movement_type' => 'Quality Release',
                'material_id' => $stockpile->material_id,
                'quantity' => $stockpile->quantity_available,
                'source_stockpile_id' => $stockpile->id,
                'operator_id' => $releasedById,
            ], $hold);
        }
    }

    /**
     * Real check used by DispatchOrder's quality gate — any Active hold
     * against the given stockpile blocks dispatch.
     */
    public function hasActiveHold(int $stockpileId): bool
    {
        return QualityHold::where('stockpile_id', $stockpileId)->where('status', 'Active')->exists();
    }
}
