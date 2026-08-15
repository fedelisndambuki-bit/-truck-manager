<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentWorkflow;
use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Drawing extends BaseModel
{
    use HasDocumentWorkflow;
    use ProjectAccessScope;

    protected $guarded = [];

    protected $casts = [
        'consultant_approved_at' => 'datetime',
        'client_approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Drawing $drawing) {
            if (empty($drawing->drawing_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $prefix = $drawing->type === 'Shop Drawing' ? 'SD' : 'DWG';
                $drawing->drawing_number = $prefix . '-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
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

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function getIsSupersededAttribute(): bool
    {
        return $this->versions()->where('is_current', false)->exists();
    }
}
