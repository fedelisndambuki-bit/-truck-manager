<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity_on_hand' => 'decimal:2',
        'reserved_quantity' => 'decimal:2',
        'damaged_quantity' => 'decimal:2',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getAvailableQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity_on_hand - (float) $this->reserved_quantity - (float) $this->damaged_quantity);
    }
}
