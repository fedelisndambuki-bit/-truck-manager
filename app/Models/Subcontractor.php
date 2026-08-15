<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subcontractor extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'insurance_expiry_date' => 'date',
        'business_license_expiry_date' => 'date',
        'nca_expiry_date' => 'date',
        'contract_value' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Subcontractor $subcontractor) {
            if (empty($subcontractor->subcontractor_code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $subcontractor->subcontractor_code = 'SUB-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(SubcontractorSpecialization::class);
    }

    public function prequalifications(): HasMany
    {
        return $this->hasMany(SubcontractorPrequalification::class);
    }

    public function bidSubmissions(): HasMany
    {
        return $this->hasMany(BidSubmission::class);
    }

    public function tenderInvitations(): BelongsToMany
    {
        return $this->belongsToMany(TenderInvitation::class, 'tender_invitation_subcontractor')
            ->withPivot(['invited_at', 'responded'])
            ->withTimestamps();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SubcontractorAssignment::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(SubcontractorPerformanceReview::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function qualityInspections(): HasMany
    {
        return $this->hasMany(QualityInspection::class);
    }

    public function safetyIncidents(): HasMany
    {
        return $this->hasMany(SafetyIncident::class);
    }

    public function materialAllocations(): HasMany
    {
        return $this->hasMany(MaterialAllocation::class);
    }

    public function equipmentAllocations(): HasMany
    {
        return $this->hasMany(EquipmentAllocation::class);
    }

    public function getLatestPrequalificationAttribute(): ?SubcontractorPrequalification
    {
        if (! class_exists(SubcontractorPrequalification::class) || ! \Illuminate\Support\Facades\Schema::hasTable('subcontractor_prequalifications')) {
            return null;
        }

        return $this->prequalifications()->latest('created_at')->first();
    }

    public function getAveragePerformanceRatingAttribute(): ?float
    {
        if (! class_exists(SubcontractorPerformanceReview::class) || ! \Illuminate\Support\Facades\Schema::hasTable('subcontractor_performance_reviews')) {
            return null;
        }

        return $this->performanceReviews()->avg('overall_rating');
    }

    public function getComplianceStatusAttribute(): string
    {
        $issues = [];

        if ($this->insurance_expiry_date && $this->insurance_expiry_date->isPast()) {
            $issues[] = 'Insurance Expired';
        }
        if ($this->business_license_expiry_date && $this->business_license_expiry_date->isPast()) {
            $issues[] = 'License Expired';
        }
        if ($this->nca_expiry_date && $this->nca_expiry_date->isPast()) {
            $issues[] = 'NCA Expired';
        }
        if ($this->tax_compliance_status === 'Non-Compliant') {
            $issues[] = 'Tax Non-Compliant';
        }

        return empty($issues) ? 'Compliant' : implode(', ', $issues);
    }
}
