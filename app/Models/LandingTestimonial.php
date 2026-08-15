<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingTestimonial extends Model
{
    protected $guarded = [];

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = trim($value);
    }

    public function setCompanyAttribute($value): void
    {
        $this->attributes['company'] = $value === null ? null : trim($value);
    }
}
