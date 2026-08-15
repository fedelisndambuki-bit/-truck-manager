<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends BaseModel
{
    protected $fillable = [
        'company_id',
        'user_id',
        'employee_id',
        'license_number',
        'risk_score',
        'performance_score',
        'commission_rate',
        'status',
    ];

    protected $casts = [
        'risk_score' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The real, enforced link to the HR Employee record — replaces the
     * fragile Employee.email == User.email runtime match this used to rely
     * on entirely (see TripObserver::creditDriverWallet()).
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TripAssignment::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DriverLocation::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(DriverIncident::class);
    }

    public function fuelReceipts(): HasMany
    {
        return $this->hasMany(DriverFuelReceipt::class);
    }

    public function pods(): HasMany
    {
        return $this->hasMany(DriverPod::class);
    }

    public function preTripInspections(): HasMany
    {
        return $this->hasMany(DriverPreTripInspection::class);
    }

    public function emergencyAlerts(): HasMany
    {
        return $this->hasMany(DriverEmergencyAlert::class);
    }
}
