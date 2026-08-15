<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends BaseModel
{
    protected $table = 'stockpile_movements';

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public const MOVEMENT_TYPES = [
        'Production Receipt', 'Transfer', 'Dispatch', 'Customer Return',
        'Adjustment', 'Waste', 'Quality Hold', 'Quality Release', 'Internal Consumption',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (StockMovement $movement) {
            if (empty($movement->movement_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $movement->movement_number = 'MOV-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($movement->date)) {
                $movement->date = now();
            }
        });
    }

    /**
     * Central helper used by every inventory-affecting model (Stock Transfer,
     * Reservation conversion, Adjustment, Physical Count, Weighbridge dispatch)
     * so each real event produces exactly one ledger row.
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

    public function sourceStockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class, 'source_stockpile_id');
    }

    public function destinationStockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class, 'destination_stockpile_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
