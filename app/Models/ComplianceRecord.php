<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceRecord extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
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
        'Safety Audit', 'Regulatory Inspection', 'Permit Compliance', 'Site Safety Plan',
        'Emergency Preparedness', 'Environmental Compliance', 'Legal Compliance', 'Insurance Compliance',
    ];
}
