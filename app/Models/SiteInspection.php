<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteInspection extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'inspector_id');
    }
}
