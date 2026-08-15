<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'expected_completion' => 'date',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (WorkOrder $workOrder) {
            if (empty($workOrder->work_order_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $workOrder->work_order_number = 'WO-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }
}
