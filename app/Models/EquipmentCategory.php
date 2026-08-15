<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentCategory extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (EquipmentCategory $category) {
            if (empty($category->code)) {
                $category->code = \Illuminate\Support\Str::slug($category->name);
            }
        });
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }
}
