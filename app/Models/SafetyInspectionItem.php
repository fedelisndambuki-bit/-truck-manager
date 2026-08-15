<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyInspectionItem extends Model
{
    protected $guarded = [];

    public function safetyInspection(): BelongsTo
    {
        return $this->belongsTo(SafetyInspection::class);
    }
}
