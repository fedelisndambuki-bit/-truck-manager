<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScopeOfWork extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'expected_completion' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ScopeOfWorkItem::class);
    }

    public function getTotalValueAttribute(): float
    {
        return (float) $this->items()->sum('amount');
    }

    public function getPercentCompleteAttribute(): float
    {
        $total = $this->items()->count();

        if ($total === 0) {
            return 0;
        }

        $completed = $this->items()->where('status', 'Completed')->count();

        return round(($completed / $total) * 100);
    }
}
