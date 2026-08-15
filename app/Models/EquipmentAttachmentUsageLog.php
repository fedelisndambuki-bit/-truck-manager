<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentAttachmentUsageLog extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'attached_at' => 'datetime',
        'detached_at' => 'datetime',
        'hours_used' => 'decimal:2',
    ];

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(EquipmentAttachment::class, 'equipment_attachment_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
