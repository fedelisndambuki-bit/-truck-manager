<?php

namespace App\Models;

use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeOrder extends BaseModel
{
    use ProjectAccessScope;

    protected $guarded = [];

    protected $casts = [
        'cost_impact' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (ChangeOrder $co) {
            if (empty($co->co_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $co->co_number = 'CO-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'Approved',
            'approved_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'Rejected']);
    }
}
