<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionTarget extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public const PERIOD_TYPES = ['Daily', 'Weekly', 'Monthly', 'Quarterly', 'Yearly'];

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function actualQuantity(): float
    {
        // ProductionOutput can be sourced from either the legacy ProductionLog
        // workflow or the newer ProductionRun (Crushing Plant Management)
        // workflow — production_log_id is nullable specifically to allow the
        // latter, so both sources must be counted or run-sourced output is
        // silently dropped from the target's achievement figures.
        $query = ProductionOutput::query()
            ->where(function ($q) {
                $q->whereHas('productionLog', function ($sub) {
                    $sub->where('extraction_site_id', $this->extraction_site_id)
                        ->where('status', 'Approved')
                        ->whereBetween('date', [$this->period_start, $this->period_end]);
                })->orWhereHas('productionRun', function ($sub) {
                    $sub->where('status', 'Approved')
                        ->whereBetween('start_time', [$this->period_start, $this->period_end])
                        ->whereHas('crushingPlant', function ($cp) {
                            $cp->where('extraction_site_id', $this->extraction_site_id);
                        });
                });
            });

        if ($this->material_id) {
            $query->where('material_id', $this->material_id);
        }

        return (float) $query->sum('quantity_produced');
    }

    public function variance(): float
    {
        return round($this->actualQuantity() - (float) $this->target_quantity, 2);
    }

    public function achievementPercent(): float
    {
        if ((float) $this->target_quantity <= 0) {
            return 0.0;
        }

        return round(($this->actualQuantity() / (float) $this->target_quantity) * 100, 1);
    }
}
