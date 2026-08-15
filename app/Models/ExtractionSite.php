<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtractionSite extends BaseModel
{
    protected $guarded = [];

    public function productionLogs(): HasMany
    {
        return $this->hasMany(ProductionLog::class);
    }

    public function crushingPlants(): HasMany
    {
        return $this->hasMany(CrushingPlant::class);
    }

    public function stockpiles(): HasMany
    {
        return $this->hasMany(Stockpile::class);
    }

    public function weighbridgeTickets(): HasMany
    {
        return $this->hasMany(WeighbridgeTicket::class);
    }

    public function fuelStock(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FuelStock::class);
    }

    public function safetyIncidents(): HasMany
    {
        return $this->hasMany(SafetyIncident::class);
    }

    public function quarryFaces(): HasMany
    {
        return $this->hasMany(QuarryFace::class);
    }

    public function pits(): HasMany
    {
        return $this->hasMany(Pit::class);
    }
}
