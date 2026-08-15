<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentWorkflow;
use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rfi extends BaseModel
{
    use HasDocumentWorkflow;
    use ProjectAccessScope;

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Rfi $rfi) {
            if (empty($rfi->rfi_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $rfi->rfi_number = 'RFI-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
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

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'raised_by_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(RfiResponse::class)->latest('responded_at');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'Open' && $this->due_date && $this->due_date->isPast();
    }
}
