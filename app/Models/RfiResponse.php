<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfiResponse extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::created(function (RfiResponse $response) {
            $response->rfi()->update(['status' => 'Answered']);
        });
    }

    public function rfi(): BelongsTo
    {
        return $this->belongsTo(Rfi::class);
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responded_by_id');
    }
}
