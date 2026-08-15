<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentWorkflow;
use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalSubmittal extends BaseModel
{
    use HasDocumentWorkflow;
    use ProjectAccessScope;

    protected $guarded = [];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (TechnicalSubmittal $submittal) {
            if (empty($submittal->submittal_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $submittal->submittal_number = 'TS-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
