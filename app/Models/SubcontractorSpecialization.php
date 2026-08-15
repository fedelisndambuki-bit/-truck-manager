<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class SubcontractorSpecialization extends BaseModel
{
    protected $guarded = [];

    public function subcontractors(): HasMany
    {
        return $this->hasMany(Subcontractor::class, 'specialization_id');
    }
}
