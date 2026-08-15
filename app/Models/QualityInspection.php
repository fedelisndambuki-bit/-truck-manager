<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class QualityInspection extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * QualityInspection has no direct project_id (only site_id), so it can't
     * use the generic App\Traits\ProjectAccessScope trait — this is the one
     * model that needs a site-join variant of the same restriction.
     */
    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('project_access', function (Builder $builder) {
            $user = Auth::user();

            if (! $user || ! $user->isProjectRestricted()) {
                return;
            }

            $projectIds = \App\Models\ProjectUser::query()
                ->where('user_id', $user->id)
                ->where('status', 'Active')
                ->pluck('project_id');

            $builder->whereHas('site', fn ($q) => $q->whereIn('project_id', $projectIds));
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'inspector_id');
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }
}
