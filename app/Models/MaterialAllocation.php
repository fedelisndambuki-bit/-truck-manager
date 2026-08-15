<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialAllocation extends BaseModel
{
    protected $guarded = [];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(MaterialConsumption::class, 'allocation_id');
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float) $this->allocated_quantity - (float) $this->consumed_quantity);
    }
}
