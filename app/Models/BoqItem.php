<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoqItem extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'completed_quantity' => 'decimal:2',
        'variation_quantity' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (BoqItem $item) {
            $item->amount = round((float) $item->quantity * (float) $item->rate, 2);
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function costCode(): BelongsTo
    {
        return $this->belongsTo(CostCode::class);
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, round((float) $this->quantity - (float) $this->completed_quantity, 2));
    }

    public function getPercentCompleteAttribute(): float
    {
        return $this->quantity > 0 ? round(((float) $this->completed_quantity / (float) $this->quantity) * 100, 1) : 0;
    }

    public function getCompletedAmountAttribute(): float
    {
        return round((float) $this->completed_quantity * (float) $this->rate, 2);
    }
}
