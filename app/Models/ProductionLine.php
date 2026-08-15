<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionLine extends BaseModel
{
    protected $guarded = [];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (ProductionLine $line) {
            if (empty($line->line_code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $line->line_code = 'LINE-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function crushingPlant(): BelongsTo
    {
        return $this->belongsTo(CrushingPlant::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }

    public function crushers(): HasMany
    {
        return $this->hasMany(Crusher::class);
    }

    public function screens(): HasMany
    {
        return $this->hasMany(Screen::class);
    }

    public function conveyors(): HasMany
    {
        return $this->hasMany(Conveyor::class);
    }

    public function feeders(): HasMany
    {
        return $this->hasMany(Feeder::class);
    }
}
