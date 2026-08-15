<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaintenanceWorkOrder extends BaseModel
{
    protected $guarded = [];

    public const TYPES = ['Preventive', 'Corrective', 'Breakdown', 'Inspection-Followup', 'Other'];

    public const PRIORITIES = ['Low', 'Medium', 'High', 'Critical'];

    public const STATUSES = ['Created', 'Assigned', 'In Progress', 'Waiting Parts', 'Completed', 'Closed'];

    protected $casts = [
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'required_parts' => 'array',
        'labor_cost' => 'decimal:2',
        'outside_services_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (MaintenanceWorkOrder $order) {
            if (empty($order->work_order_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $order->work_order_number = 'MWO-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'technician_id');
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function workshopBay(): BelongsTo
    {
        return $this->belongsTo(WorkshopBay::class);
    }

    public function preventiveMaintenancePlan(): BelongsTo
    {
        return $this->belongsTo(PreventiveMaintenancePlan::class);
    }

    public function breakdown(): HasOne
    {
        return $this->hasOne(Breakdown::class, 'maintenance_work_order_id');
    }

    public function serviceHistory(): HasOne
    {
        return $this->hasOne(Maintenance::class, 'maintenance_work_order_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }
}
