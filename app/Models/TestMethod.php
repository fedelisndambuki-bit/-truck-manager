<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TestMethod extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'applicable_products' => 'array',
        'acceptance_criteria' => 'array',
    ];

    public const STATUSES = ['Active', 'Deprecated'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (TestMethod $method) {
            if (empty($method->method_code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $method->method_code = 'MTH-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($method->status)) {
                $method->status = 'Active';
            }
        });
    }

    public function qualityStandard(): BelongsTo
    {
        return $this->belongsTo(QualityStandard::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(LabTest::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(LabDocument::class, 'documentable');
    }
}
