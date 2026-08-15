<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NearMiss extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'photos' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (NearMiss $nearMiss) {
            if (empty($nearMiss->near_miss_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $nearMiss->near_miss_number = 'NM-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
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

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reported_by_id');
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }
}
