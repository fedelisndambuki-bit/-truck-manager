<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyItem extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'details' => 'array',
        'next_check_date' => 'date',
        'last_checked_at' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function responsibleStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responsible_staff_id');
    }

    public const TYPES = [
        'Emergency Contact', 'Response Plan', 'Assembly Point', 'Fire Equipment',
        'First Aid Kit', 'Drill', 'Team Member',
    ];
}
