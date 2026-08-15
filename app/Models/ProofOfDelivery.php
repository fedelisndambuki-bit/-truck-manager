<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProofOfDelivery extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'delivery_time' => 'datetime',
    ];

    public const STATUSES = ['Delivered', 'Partial Delivery', 'Rejected', 'Returned'];

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }
}
