<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialRequest extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'required_date' => 'date',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (MaterialRequest $request) {
            if (empty($request->request_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $request->request_number = 'MR-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialRequestItem::class);
    }
}
