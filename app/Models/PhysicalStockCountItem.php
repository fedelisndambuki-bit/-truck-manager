<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhysicalStockCountItem extends BaseModel
{
    protected $guarded = [];

    public function physicalStockCount(): BelongsTo
    {
        return $this->belongsTo(PhysicalStockCount::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
