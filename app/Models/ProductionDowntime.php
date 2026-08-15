<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionDowntime extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public const REASONS = [
        'Mechanical Failure', 'Electrical Failure', 'Scheduled Maintenance', 'Fuel Shortage',
        'Weather', 'Operator Issue', 'Material Shortage', 'Safety Incident',
    ];

    protected static function booted()
    {
        parent::booted();

        static::saving(function (ProductionDowntime $downtime) {
            if ($downtime->start_time && $downtime->end_time) {
                $downtime->duration_minutes = max(0, $downtime->start_time->diffInMinutes($downtime->end_time));
            }
        });
    }

    public function productionLog(): BelongsTo
    {
        return $this->belongsTo(ProductionLog::class);
    }

    public function crushingPlant(): BelongsTo
    {
        return $this->belongsTo(CrushingPlant::class);
    }

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function crusher(): BelongsTo
    {
        return $this->belongsTo(Crusher::class);
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function conveyor(): BelongsTo
    {
        return $this->belongsTo(Conveyor::class);
    }

    public function feeder(): BelongsTo
    {
        return $this->belongsTo(Feeder::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reported_by_id');
    }

    public function affectedEquipmentLabel(): ?string
    {
        return $this->crusher?->crusher_code
            ?? $this->screen?->name
            ?? $this->conveyor?->name
            ?? $this->feeder?->name
            ?? null;
    }
}
