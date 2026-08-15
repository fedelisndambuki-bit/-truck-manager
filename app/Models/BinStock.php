<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BinStock extends BaseModel
{
    protected $table = 'bin_stock';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function binLocation(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
