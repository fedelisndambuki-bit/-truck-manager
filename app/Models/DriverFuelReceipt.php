<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverFuelReceipt extends Model
{
    protected $fillable = [
        'driver_id',
        'trip_id',
        'vendor',
        'litres',
        'amount',
        'receipt_path',
        'status',
        'failure_reason',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'litres' => 'decimal:2',
        'amount' => 'decimal:2',
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
