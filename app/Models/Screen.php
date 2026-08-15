<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Screen extends BaseModel
{
    protected $guarded = [];

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
