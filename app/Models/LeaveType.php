<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends BaseModel
{
    protected $guarded = [];

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
