<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feeder extends BaseModel
{
    protected $guarded = [];

    public const TYPES = ['Vibrating', 'Apron', 'Belt'];

    public const STATUSES = ['Running', 'Idle', 'Maintenance', 'Breakdown'];

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function downtimes(): HasMany
    {
        return $this->hasMany(ProductionDowntime::class);
    }
}
