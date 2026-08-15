<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Breakdown extends BaseModel
{
    protected $guarded = [];

    public const SEVERITIES = ['Low', 'Medium', 'High', 'Critical'];

    public const STATUSES = ['Reported', 'Assigned', 'Resolved'];

    protected $casts = [
        'occurred_at' => 'datetime',
        'photos' => 'array',
        'videos' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Breakdown $breakdown) {
            if (empty($breakdown->breakdown_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $breakdown->breakdown_number = 'BRK-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(MaintenanceWorkOrder::class, 'maintenance_work_order_id');
    }
}
