<?php

namespace App\Models;

use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends BaseModel
{
    protected $fillable = [
        'company_id',
        'truck_id',
        'employee_id',
        'client_id',
        'cargo_id',
        'route_name',
        'origin',
        'destination',
        'cargo_type',
        'cargo_weight',
        'client_name',
        'client_contact',
        'notes',
        'departure_time',
        'expected_arrival',
        'actual_arrival',
        'status',
        'distance_km',
        'fuel_litres',
        'fuel_cost',
        'trip_expenses',
        'revenue',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'expected_arrival' => 'datetime',
        'actual_arrival' => 'datetime',
        'distance_km' => 'decimal:2',
        'cargo_weight' => 'decimal:2',
        'fuel_litres' => 'decimal:2',
        'fuel_cost' => 'decimal:2',
        'trip_expenses' => 'decimal:2',
        'revenue' => 'decimal:2',
        'status' => TripStatus::class,
    ];

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function assignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TripAssignment::class, 'trip_id', 'id');
    }

    public function expenses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
