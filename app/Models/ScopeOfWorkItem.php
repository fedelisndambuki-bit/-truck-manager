<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScopeOfWorkItem extends BaseModel
{
    protected $guarded = [];

    protected static function booted()
    {
        parent::booted();

        static::saving(function (ScopeOfWorkItem $item) {
            $item->amount = (float) $item->quantity * (float) $item->unit_rate;
        });
    }

    public function scopeOfWork(): BelongsTo
    {
        return $this->belongsTo(ScopeOfWork::class);
    }
}
