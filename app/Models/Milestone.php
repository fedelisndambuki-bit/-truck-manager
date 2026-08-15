<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date' => 'date',
        'is_critical_path' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(Milestone::class, 'depends_on_milestone_id');
    }
}
