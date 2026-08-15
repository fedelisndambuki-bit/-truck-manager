<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelematicsReading extends BaseModel
{
    protected $guarded = [];

    public const SOURCES = ['Manual', 'CAT-ProductLink', 'Komtrax', 'CareTrack', 'e-Service', 'JDLink', 'LiveLink'];

    protected $casts = [
        'recorded_at' => 'datetime',
        'gps_lat' => 'decimal:6',
        'gps_lng' => 'decimal:6',
        'engine_hours' => 'decimal:2',
        'fault_codes' => 'array',
        'fuel_level' => 'decimal:2',
        'speed_kph' => 'decimal:2',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
