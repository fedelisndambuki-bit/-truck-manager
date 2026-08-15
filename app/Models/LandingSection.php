<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingSection extends Model
{
    protected $guarded = [];

    public static function forKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}
