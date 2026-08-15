<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends BaseModel
{
    protected $guarded = [];

    public function head(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'head_staff_id');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
