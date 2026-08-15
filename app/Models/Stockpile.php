<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Stockpile extends BaseModel
{
    protected $guarded = [];

    public const STATUSES = ['Active', 'Inactive', 'Maintenance', 'Closed'];

    public const LOCATION_TYPES = ['Stockyard', 'Depot', 'Storage Area'];

    public const QUALITY_STATUSES = ['Pending Test', 'Approved', 'Rejected', 'On Hold', 'Expired'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Stockpile $stockpile) {
            if (empty($stockpile->stockpile_code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $stockpile->stockpile_code = 'STK-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function crushingPlant(): BelongsTo
    {
        return $this->belongsTo(CrushingPlant::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(StockpileDocument::class, 'documentable');
    }

    /**
     * A stockpile can appear as either the source or the destination of a
     * movement, so this is a real query helper rather than a single hasMany.
     */
    public function movements()
    {
        return StockMovement::query()
            ->where('source_stockpile_id', $this->id)
            ->orWhere('destination_stockpile_id', $this->id)
            ->latest('date');
    }

    public function isLow(): bool
    {
        return (float) $this->quantity_available <= (float) $this->min_level;
    }

    public function isFull(): bool
    {
        return $this->max_level !== null && (float) $this->quantity_available >= (float) $this->max_level;
    }

    public function availableCapacity(): ?float
    {
        if ($this->max_level === null) {
            return null;
        }

        return max(0.0, (float) $this->max_level - (float) $this->quantity_available);
    }

    public function availableForSale(): float
    {
        return max(0.0, (float) $this->quantity_available - (float) $this->quantity_reserved);
    }

    /**
     * Falls back to the linked Material's cost basis when this stockpile has
     * no unit_value of its own set, matching Material/Warehouse's existing
     * average_cost ?? unit_cost convention — otherwise a stockpile with no
     * unit_value silently values at zero even with a real cost basis.
     */
    public function value(): float
    {
        $unitValue = $this->unit_value ?? $this->material?->average_cost ?? $this->material?->unit_cost ?? 0;

        return (float) $this->quantity_available * (float) $unitValue;
    }
}
