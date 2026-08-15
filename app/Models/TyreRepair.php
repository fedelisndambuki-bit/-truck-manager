<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TyreRepair extends BaseModel
{
    protected $fillable = [
        'company_id',
        'tyre_id',
        'repair_date',
        'repair_type', // puncture, vulcanization, retread
        'cost',
        'vendor',
        'notes',
    ];

    protected $casts = [
        'repair_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function tyre(): BelongsTo
    {
        return $this->belongsTo(Tyre::class);
    }
}
