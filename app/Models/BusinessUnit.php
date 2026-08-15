<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessUnit extends BaseModel
{
    protected $guarded = [];

    public const STATUSES = ['Active', 'Inactive'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (BusinessUnit $unit) {
            if (empty($unit->code)) {
                $unit->code = \Illuminate\Support\Str::slug($unit->name);
            }
        });
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'manager_staff_id');
    }
}
