<?php

namespace App\Models;

use App\Enums\TruckStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Truck extends BaseModel
{
    protected $fillable = [
        'company_id',
        'plate_number', 'truck_type', 'axle_config', 'tyre_count', 'brand', 'model', 'year', 'engine_number',
        'chassis_number', 'fuel_type', 'fuel_consumption', 'current_mileage',
        'capacity_tons', 'status', 'insurance_provider', 'insurance_expiry',
        'road_license_expiry', 'purchase_date', 'notes', 'truck_image',
    ];

    protected $casts = [
        'status' => TruckStatus::class,
    ];

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function tyres(): HasMany
    {
        return $this->hasMany(Tyre::class);
    }

    public function getLastServiceDateAttribute()
    {
        return $this->maintenances()->latest('maintenance_date')->first()?->maintenance_date;
    }

    // Link to the Driver currently assigned to this truck
    public function driver(): HasOne
    {
        return $this->hasOne(Employee::class)->where('status', 'Active');
    }
}
