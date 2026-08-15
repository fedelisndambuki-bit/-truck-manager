<?php

namespace App\Models;

use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends BaseModel
{
    use ProjectAccessScope;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'expected_completion_date' => 'date',
        'progress_percent' => 'integer',
        'night_shift_enabled' => 'boolean',
        'weather_monitoring_enabled' => 'boolean',
        'fire_equipment_available' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Site $site) {
            if (empty($site->site_code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $site->site_code = 'SITE-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function siteManager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'site_manager_id');
    }

    public function equipmentAllocations(): HasMany
    {
        return $this->hasMany(EquipmentAllocation::class);
    }

    public function labourers(): HasMany
    {
        return $this->hasMany(Labourer::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function materialDeliveries(): HasMany
    {
        return $this->hasMany(MaterialDelivery::class);
    }

    public function subcontractors(): HasMany
    {
        return $this->hasMany(Subcontractor::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(SiteInspection::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(SafetyIncident::class);
    }

    public function qualityInspections(): HasMany
    {
        return $this->hasMany(QualityInspection::class);
    }

    public function diaries(): HasMany
    {
        return $this->hasMany(DailySiteDiary::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(SiteTeamMember::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(SiteZone::class);
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function materialAllocations(): HasMany
    {
        return $this->hasMany(MaterialAllocation::class);
    }

    public function nearMisses(): HasMany
    {
        return $this->hasMany(NearMiss::class);
    }

    public function hazards(): HasMany
    {
        return $this->hasMany(Hazard::class);
    }

    public function riskAssessments(): HasMany
    {
        return $this->hasMany(RiskAssessment::class);
    }

    public function safetyInspections(): HasMany
    {
        return $this->hasMany(SafetyInspection::class);
    }

    public function toolboxTalks(): HasMany
    {
        return $this->hasMany(ToolboxTalk::class);
    }

    public function safetyMeetings(): HasMany
    {
        return $this->hasMany(SafetyMeeting::class);
    }

    public function emergencyItems(): HasMany
    {
        return $this->hasMany(EmergencyItem::class);
    }

    public function complianceRecords(): HasMany
    {
        return $this->hasMany(ComplianceRecord::class);
    }

    public function teamSlot(string $slot): ?Staff
    {
        return $this->teamMembers->firstWhere('role_slot', $slot)?->employee;
    }
}
