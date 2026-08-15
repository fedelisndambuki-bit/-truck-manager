<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentWorkflow;
use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MethodStatement extends BaseModel
{
    use HasDocumentWorkflow;
    use ProjectAccessScope;

    protected $guarded = [];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (MethodStatement $ms) {
            if (empty($ms->reference_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $ms->reference_number = 'MS-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function riskAssessment(): BelongsTo
    {
        return $this->belongsTo(RiskAssessment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
