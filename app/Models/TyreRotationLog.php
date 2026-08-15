<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TyreRotationLog extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'rotated_at' => 'datetime',
        'mileage_at_rotation' => 'integer',
    ];

    public function tyre(): BelongsTo
    {
        return $this->belongsTo(Tyre::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'performed_by_id');
    }
}
