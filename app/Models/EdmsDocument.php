<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentWorkflow;
use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EdmsDocument extends BaseModel
{
    use HasDocumentWorkflow;
    use ProjectAccessScope;

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
        'approval_date' => 'datetime',
        'review_date' => 'date',
        'expiry_date' => 'date',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (EdmsDocument $document) {
            if (empty($document->document_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $document->document_number = 'DOC-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'owner_staff_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }

    public function transmittals(): BelongsToMany
    {
        return $this->belongsToMany(Transmittal::class, 'transmittal_items', 'document_id', 'transmittal_id');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getFileSizeForHumansAttribute(): string
    {
        $bytes = (int) ($this->file_size ?? 0);

        if ($bytes <= 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 1) . ' ' . $units[$power];
    }
}
