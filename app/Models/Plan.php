<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
    ];

    public function getNameAttribute($value)
    {
        return $value ?: 'Unnamed Plan #' . $this->id;
    }

    protected static function booted(): void
    {
        static::saved(function (Plan $plan) {
            if ($plan->is_default) {
                static::where('id', '!=', $plan->id)->update(['is_default' => false]);
            }
        });
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'plan_module');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
