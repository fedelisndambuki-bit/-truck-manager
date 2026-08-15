<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transmittal extends BaseModel
{
    use HasDocumentWorkflow;

    protected $guarded = [];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Transmittal $transmittal) {
            if (empty($transmittal->transmittal_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $transmittal->transmittal_number = 'TR-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'sender_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransmittalItem::class);
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(EdmsDocument::class, 'transmittal_items', 'transmittal_id', 'document_id');
    }

    public function drawings(): BelongsToMany
    {
        return $this->belongsToMany(Drawing::class, 'transmittal_items', 'transmittal_id', 'drawing_id');
    }

    public function acknowledge(): void
    {
        $this->update(['acknowledged_at' => now()]);
    }
}
