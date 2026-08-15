<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status' => 'boolean',
        'grayscale' => 'boolean',
    ];

    const CATEGORIES = [
        'technology' => 'Technology Partner',
        'integration' => 'Integration Partner',
        'industry' => 'Industry Partner',
    ];

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = trim($value);
    }
}
