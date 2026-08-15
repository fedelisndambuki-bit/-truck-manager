<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:2',
    ];

    public const MOVEMENT_TYPES = [
        'Receipt', 'Issue', 'Transfer', 'Return', 'Consumption', 'Adjustment', 'PutAway',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (InventoryMovement $movement) {
            if (empty($movement->movement_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $movement->movement_number = 'IMV-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($movement->date)) {
                $movement->date = now();
            }
        });
    }

    /**
     * Central helper used by every general-materials inventory-affecting
     * model (GRN, Stock Transfer, Stock Adjustment, Material Consumption,
     * Material Return, Put-Away) so each real event produces exactly one
     * ledger row. Mirrors App\Models\StockMovement::record() — the
     * equivalent factory for the separate quarry Stockpile ledger.
     */
    public static function record(array $attributes, ?Model $reference = null): self
    {
        if ($reference) {
            $attributes['reference_type'] = $reference->getMorphClass();
            $attributes['reference_id'] = $reference->getKey();
        }

        return static::create($attributes);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function sourceBin(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class, 'source_bin_id');
    }

    public function destinationBin(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class, 'destination_bin_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
