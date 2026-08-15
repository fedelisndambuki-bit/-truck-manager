<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentAttachment extends BaseModel
{
    protected $guarded = [];

    public const TYPES = ['Bucket', 'Breaker', 'Ripper', 'Fork', 'Hydraulic Hammer', 'Quick Coupler', 'Other'];

    public const STATUSES = ['Available', 'In Use', 'Under Maintenance', 'Out of Service'];

    protected $casts = [
        'compatible_equipment_types' => 'array',
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'total_usage_hours' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (EquipmentAttachment $attachment) {
            if (empty($attachment->attachment_code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $attachment->attachment_code = 'ATT-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function currentEquipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'current_equipment_id');
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(EquipmentAttachmentUsageLog::class);
    }
}
