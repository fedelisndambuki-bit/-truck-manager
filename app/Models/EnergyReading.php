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
        // CrushingPlantLog has no live write path in this app (seeder-only) —
        // ProductionRun is the model actually credited by real approvals, so
        // it must be counted too or throughput (and every ratio derived from
        // it) is understated.
        $throughput = (float) CrushingPlantLog::query()
            ->where('crushing_plant_id', $this->crushing_plant_id)
            ->whereDate('date', $this->date)
            ->sum('output_tonnes');

        $throughput += (float) ProductionRun::query()
            ->where('crushing_plant_id', $this->crushing_plant_id)
            ->whereIn('status', ['Approved', 'Completed'])
            ->whereDate('start_time', $this->date)
            ->sum('output_quantity');

        if ($throughput <= 0 || ! $this->diesel_litres) {
            return null;
        }

        return round((float) $this->diesel_litres / $throughput, 2);
    }

    public function costPerTonne(): ?float
    {
        // CrushingPlantLog has no live write path in this app (seeder-only) —
        // ProductionRun is the model actually credited by real approvals, so
        // it must be counted too or throughput (and every ratio derived from
        // it) is understated.
        $throughput = (float) CrushingPlantLog::query()
            ->where('crushing_plant_id', $this->crushing_plant_id)
            ->whereDate('date', $this->date)
            ->sum('output_tonnes');

        $throughput += (float) ProductionRun::query()
            ->where('crushing_plant_id', $this->crushing_plant_id)
            ->whereIn('status', ['Approved', 'Completed'])
            ->whereDate('start_time', $this->date)
            ->sum('output_quantity');

        if ($throughput <= 0) {
            return null;
        }

        return round((float) $this->energy_cost / $throughput, 2);
    }
}
