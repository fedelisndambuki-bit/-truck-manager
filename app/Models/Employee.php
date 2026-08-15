<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends BaseModel
{
    protected $fillable = [
        'company_id',
        'full_name',
        'phone_number',
        'email',
        'national_id',
        'license_number',
        'license_expiry',
        'category',
        'truck_id',
        'status',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'date_hired',
        'notes',
        'profile_photo',
        'base_salary',
        'allowances',
        'tax_deduction',
        'tax_code',
        'position',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'date_hired' => 'date',
        'base_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'tax_deduction' => 'decimal:2',
    ];

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function driver(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class, 'employee_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'employee_id');
    }
}
