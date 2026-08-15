<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visitor extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'host_employee_id');
    }
}
