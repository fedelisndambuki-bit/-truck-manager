<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlastCompliance extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'permit_expiry' => 'date',
        'regulatory_approval' => 'boolean',
        'blast_notifications_sent' => 'boolean',
        'inspector_approval' => 'boolean',
        'safety_checklist_complete' => 'boolean',
        'environmental_checklist_complete' => 'boolean',
        'legal_compliance' => 'boolean',
    ];

    public function blastPlan(): BelongsTo
    {
        return $this->belongsTo(BlastPlan::class);
    }

    public function scorePercent(): int
    {
        $flags = [
            $this->regulatory_approval, $this->blast_notifications_sent, $this->inspector_approval,
            $this->safety_checklist_complete, $this->environmental_checklist_complete, $this->legal_compliance,
        ];

        return (int) round((count(array_filter($flags)) / count($flags)) * 100);
    }
}
