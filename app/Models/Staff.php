<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_hired' => 'date',
        'contract_expiry' => 'date',
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'ppe_sizes' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Staff $staff) {
            if (empty($staff->staff_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $staff->staff_number = 'STAFF-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Staff::class, 'supervisor_id');
    }

    public function labourerAssignments(): HasMany
    {
        return $this->hasMany(Labourer::class, 'employee_id');
    }

    public function projectTeamMemberships(): HasMany
    {
        return $this->hasMany(ProjectTeamMember::class, 'employee_id');
    }

    public function siteTeamMemberships(): HasMany
    {
        return $this->hasMany(SiteTeamMember::class, 'employee_id');
    }

    public function equipmentOperations(): HasMany
    {
        return $this->hasMany(EquipmentAllocation::class, 'operator_employee_id');
    }

    public function equipmentBackupOperations(): HasMany
    {
        return $this->hasMany(EquipmentAllocation::class, 'backup_operator_employee_id');
    }

    public function equipmentSupervisions(): HasMany
    {
        return $this->hasMany(EquipmentAllocation::class, 'supervisor_employee_id');
    }

    public function siteInspections(): HasMany
    {
        return $this->hasMany(SiteInspection::class, 'inspector_id');
    }

    public function reportedIncidents(): HasMany
    {
        return $this->hasMany(SafetyIncident::class, 'reported_by_id');
    }

    public function qualityInspections(): HasMany
    {
        return $this->hasMany(QualityInspection::class, 'inspector_id');
    }

    public function siteDiaries(): HasMany
    {
        return $this->hasMany(DailySiteDiary::class, 'logged_by_id');
    }

    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'project_manager_id');
    }

    public function managedSites(): HasMany
    {
        return $this->hasMany(Site::class, 'site_manager_id');
    }

    public function equipmentInspections(): HasMany
    {
        return $this->hasMany(EquipmentInspection::class, 'inspector_id');
    }

    public function materialDeliveriesReceived(): HasMany
    {
        return $this->hasMany(MaterialDelivery::class, 'received_by_id');
    }

    public function hostedVisitors(): HasMany
    {
        return $this->hasMany(Visitor::class, 'host_employee_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function productionLogs(): HasMany
    {
        return $this->hasMany(ProductionLog::class, 'logged_by_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'staff_id');
    }

    public function managedWarehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'manager_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function salaryAdvances(): HasMany
    {
        return $this->hasMany(SalaryAdvance::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function disciplinaryCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class);
    }

    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class);
    }

    public function ppeIssuances(): HasMany
    {
        return $this->hasMany(PpeIssuance::class);
    }

    public function reportedNearMisses(): HasMany
    {
        return $this->hasMany(NearMiss::class, 'reported_by_id');
    }

    public function responsibleHazards(): HasMany
    {
        return $this->hasMany(Hazard::class, 'responsible_staff_id');
    }

    public function safetyInspectionsConducted(): HasMany
    {
        return $this->hasMany(SafetyInspection::class, 'inspector_id');
    }

    public function toolboxTalksPresented(): HasMany
    {
        return $this->hasMany(ToolboxTalk::class, 'presenter_id');
    }

    public function correctiveActionsResponsible(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class, 'responsible_staff_id');
    }

    public function medicalCases(): HasMany
    {
        return $this->hasMany(MedicalCase::class);
    }
}
