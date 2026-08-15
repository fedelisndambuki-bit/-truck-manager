<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitCentre extends BaseModel
{
    protected $table = 'fin_profit_centres';

    protected $guarded = [];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'manager_staff_id');
    }
}
