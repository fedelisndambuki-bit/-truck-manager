<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class StockpileAdjustment extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    public const STATUSES = ['Draft', 'Submitted', 'Approved', 'Rejected'];

    public const REASONS = [
        'Measurement Correction', 'Shrinkage', 'Spillage', 'Damage', 'Theft', 'Survey Update', 'Manual Correction',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (StockpileAdjustment $adjustment) {
            if (empty($adjustment->adjustment_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $adjustment->adjustment_number = 'ADJ-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });

        static::updated(function (StockpileAdjustment $adjustment) {
            if ($adjustment->wasChanged('status') && $adjustment->status === 'Approved') {
                $adjustment->apply();
            }
        });
    }

    public function apply(): void
    {
        $this->stockpile?->increment('quantity_available', $this->quantity);

        StockMovement::record([
            'company_id' => $this->company_id,
            'movement_type' => 'Adjustment',
            'material_id' => $this->stockpile?->material_id,
            'quantity' => abs($this->quantity),
            'destination_stockpile_id' => $this->quantity >= 0 ? $this->stockpile_id : null,
            'source_stockpile_id' => $this->quantity < 0 ? $this->stockpile_id : null,
        ], $this);
    }

    public function stockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(StockpileDocument::class, 'documentable');
    }
}
