<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeighbridgeWeighingLog extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public const WEIGHING_TYPES = ['Tare', 'Gross'];

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }

    public function weighbridge(): BelongsTo
    {
        return $this->belongsTo(Weighbridge::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }
}
