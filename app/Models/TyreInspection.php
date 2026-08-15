<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TyreInspection extends BaseModel
{
    protected $fillable = [
        'company_id',
        'tyre_id',
        'inspection_date',
        'tread_depth',
        'pressure',
        'temperature',
        'checklist',
        'technician_id',
        'notes',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'tread_depth' => 'decimal:2',
        'pressure' => 'decimal:2',
        'temperature' => 'decimal:2',
        'checklist' => 'json',
    ];

    public function tyre(): BelongsTo
    {
        return $this->belongsTo(Tyre::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
