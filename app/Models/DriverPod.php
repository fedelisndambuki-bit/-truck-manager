<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverPod extends BaseModel
{
    protected $fillable = [
        'company_id',
        'driver_id',
        'trip_id',
        'image_path',
        'notes',
        'receiver_name',
        'receiver_signature',
        'pickup_details',
        'dropoff_details',
        'document_ocr_text',
        'status',
        'failure_reason',
        'delivered_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
