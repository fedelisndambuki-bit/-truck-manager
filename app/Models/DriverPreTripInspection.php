<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverPreTripInspection extends Model
{
    protected $fillable = [
        'driver_id',
        'trip_id',
        'brakes',
        'lights',
        'tires',
        'load_secured',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'brakes' => 'boolean',
        'lights' => 'boolean',
        'tires' => 'boolean',
        'load_secured' => 'boolean',
        'submitted_at' => 'datetime',
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
