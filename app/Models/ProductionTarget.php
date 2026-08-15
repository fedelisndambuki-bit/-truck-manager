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
        $query = ProductionOutput::query()
            ->whereHas('productionLog', function ($q) {
                $q->where('extraction_site_id', $this->extraction_site_id)
                    ->where('status', 'Approved')
                    ->whereBetween('date', [$this->period_start, $this->period_end]);
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
