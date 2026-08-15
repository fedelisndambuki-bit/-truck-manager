<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crusher extends BaseModel
{
    protected $guarded = [];

    public const TYPES = [
        'Primary Jaw Crusher', 'Secondary Cone Crusher', 'Impact Crusher',
        'Vertical Shaft Impact Crusher (VSI)', 'Hammer Crusher',
    ];

    public const STATUSES = ['Running', 'Idle', 'Maintenance', 'Breakdown'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Crusher $crusher) {
            if (empty($crusher->crusher_code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $crusher->crusher_code = 'CRSH-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

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
