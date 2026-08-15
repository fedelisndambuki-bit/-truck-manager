<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calibration extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'calibration_date' => 'date',
        'due_date' => 'date',
    ];

    public const STATUSES = ['Valid', 'Overdue', 'Expired'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Calibration $calibration) {
            if (empty($calibration->calibration_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $calibration->calibration_number = 'CAL-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });

        static::created(function (Calibration $calibration) {
            $calibration->labEquipment?->update([
                'calibration_due_date' => $calibration->due_date,
                'status' => 'Operational',
            ]);
        });
    }

    public function labEquipment(): BelongsTo
    {
        return $this->belongsTo(LabEquipment::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'performed_by_id');
    }
}
