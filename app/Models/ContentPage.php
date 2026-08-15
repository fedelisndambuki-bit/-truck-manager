<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentPage extends Model
{
    protected $guarded = [];

    public static function forSlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
