<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchOrderLine extends BaseModel
{
    protected $guarded = [];

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function stockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class);
    }
}
