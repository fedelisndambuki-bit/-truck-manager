<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockpilePhysicalCount extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    protected $casts = [
        'count_date' => 'date',
    ];

    public const STATUSES = ['Draft', 'Submitted', 'Approved', 'Rejected'];

    public const SURVEY_METHODS = ['Drone Survey', 'GPS Survey', 'Manual Survey', 'Laser Survey'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (StockpilePhysicalCount $count) {
            if (empty($count->count_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $count->count_number = 'CNT-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($count->system_quantity) && $count->stockpile_id) {
                $count->system_quantity = Stockpile::withoutGlobalScope('company')->find($count->stockpile_id)?->quantity_available ?? 0;
            }
        });

        static::updated(function (StockpilePhysicalCount $count) {
            if ($count->wasChanged('status') && $count->status === 'Approved') {
                $count->apply();
            }
        });
    }

    public function apply(): void
    {
        $stockpile = $this->stockpile;
        $variance = $this->variance();

        $stockpile?->update(['quantity_available' => $this->physical_quantity]);

        StockMovement::record([
            'company_id' => $this->company_id,
            'movement_type' => 'Adjustment',
            'material_id' => $stockpile?->material_id,
            'quantity' => abs($variance),
            'destination_stockpile_id' => $variance >= 0 ? $this->stockpile_id : null,
            'source_stockpile_id' => $variance < 0 ? $this->stockpile_id : null,
        ], $this);
    }

    public function variance(): float
    {
        return round((float) $this->physical_quantity - (float) $this->system_quantity, 2);
    }

    public function variancePercent(): float
    {
        if ((float) $this->system_quantity <= 0) {
            return 0.0;
        }

        return round(($this->variance() / (float) $this->system_quantity) * 100, 1);
    }

    public function stockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class);
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'counted_by_id');
    }
}
