<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlastCost extends BaseModel
{
    protected $guarded = [];

    public function blastPlan(): BelongsTo
    {
        return $this->belongsTo(BlastPlan::class);
    }

    public function totalCost(): float
    {
        return round(
            (float) $this->drilling_cost + (float) $this->explosive_cost + (float) $this->labour_cost
            + (float) $this->equipment_cost + (float) $this->fuel_cost + (float) $this->security_cost
            + (float) $this->environmental_monitoring_cost,
            2
        );
    }

    /**
     * Cost per tonne against production yielded the same day at the same
     * quarry site as this blast's execution — a defensible real proxy (no
     * direct blast_id linkage exists on production_logs), not fabricated.
     */
    public function costPerTonne(): ?float
    {
        $blastPlan = $this->blastPlan;
        $actualDate = $blastPlan?->execution?->actual_date;

        if (! $actualDate) {
            return null;
        }

        $tonnes = (float) ProductionLog::query()
            ->where('extraction_site_id', $blastPlan->extraction_site_id)
            ->whereDate('date', $actualDate)
            ->sum('quantity_tonnes');

        if ($tonnes <= 0) {
            return null;
        }

        return round($this->totalCost() / $tonnes, 2);
    }
}
