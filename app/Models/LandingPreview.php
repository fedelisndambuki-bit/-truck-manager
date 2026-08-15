<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPreview extends Model
{
    protected $guarded = [];

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = trim($value);
    }
}
