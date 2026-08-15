<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'contract_value' => 'decimal:2',
        'retention_percent' => 'decimal:2',
        'start_date' => 'date',
        'completion_date' => 'date',
        'signed_date' => 'date',
        'insurance_required' => 'boolean',
        'performance_bond_amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Contract $contract) {
            if (empty($contract->contract_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $contract->contract_number = 'CON-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function tenderInvitation(): BelongsTo
    {
        return $this->belongsTo(TenderInvitation::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SubcontractorAssignment::class);
    }

    public function scopeOfWorks(): HasMany
    {
        return $this->hasMany(ScopeOfWork::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(ProgressUpdate::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(Variation::class);
    }

    public function paymentCertificates(): HasMany
    {
        return $this->hasMany(PaymentCertificate::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function getApprovedVariationValueAttribute(): float
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('variations')) {
            return 0;
        }

        return (float) $this->variations()->where('status', 'Approved')->sum('cost_impact');
    }

    public function getRevisedContractValueAttribute(): float
    {
        return (float) $this->contract_value + $this->approved_variation_value;
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->completion_date
            && $this->completion_date->isFuture()
            && $this->completion_date->diffInDays(now()) <= 30
            && in_array($this->status, ['Active', 'Suspended']);
    }
}
