<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantDocument extends BaseModel
{
    protected $guarded = [];

    public const CATEGORIES = [
        'Plant Manual', 'Process Flow Diagram', 'Maintenance Records', 'Inspection Reports',
        'Permits', 'Safety Plans', 'Photos', 'Videos', 'Survey Reports',
    ];

    public function crushingPlant(): BelongsTo
    {
        return $this->belongsTo(CrushingPlant::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
