<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyReading extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function crushingPlant(): BelongsTo
    {
        return $this->belongsTo(CrushingPlant::class);
    }

    public function fuelEfficiency(): ?float
    {
        $throughput = (float) CrushingPlantLog::query()
            ->where('crushing_plant_id', $this->crushing_plant_id)
            ->whereDate('date', $this->date)
            ->sum('output_tonnes');

        if ($throughput <= 0 || ! $this->diesel_litres) {
            return null;
        }

        return round((float) $this->diesel_litres / $throughput, 2);
    }

    public function costPerTonne(): ?float
    {
        $throughput = (float) CrushingPlantLog::query()
            ->where('crushing_plant_id', $this->crushing_plant_id)
            ->whereDate('date', $this->date)
            ->sum('output_tonnes');

        if ($throughput <= 0) {
            return null;
        }

        return round((float) $this->energy_cost / $throughput, 2);
    }
}
