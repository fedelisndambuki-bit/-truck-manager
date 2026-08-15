<?php

namespace App\Models;

use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends BaseModel
{
    use ProjectAccessScope;

    protected $fillable = [
        'category',
        'status',
        'amount',
        'date',
        'payment_method',
        'truck_id',
        'employee_id',
        'trip_id',
        'fuel_log_id',
        'settlement_id',
        'maintenance_id',
        'approver_id',
        'approved_at',
        'receipt_upload',
        'tax_type',
        'tax_amount',
        'tax_breakdown',
        'payslip_earning_type',
        'payslip_deduction_type',
        'hours',
        'rate',
        'description',
        'company_id',
        'project_id',
    ];

    protected $casts = [
        'tax_breakdown' => 'array',
    ];

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approver_id');
    }

    public function fuelLog(): BelongsTo
    {
        return $this->belongsTo(FuelLog::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
