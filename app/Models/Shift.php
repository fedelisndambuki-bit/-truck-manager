<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
