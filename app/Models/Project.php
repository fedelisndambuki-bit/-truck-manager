<?php

namespace App\Models;

use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends BaseModel
{
    use ProjectAccessScope;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'tender_date' => 'date',
        'contract_signing_date' => 'date',
        'warranty_end_date' => 'date',
        'actual_completion_date' => 'date',
        'contract_value' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'expected_revenue' => 'decimal:2',
        'retention_percent' => 'decimal:2',
        'advance_payment' => 'decimal:2',
        'progress_percent' => 'integer',
        'estimated_duration_days' => 'integer',
        'weather_monitoring_enabled' => 'boolean',
        'is_archived' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Project $project) {
            if (empty($project->project_code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $project->project_code = 'PRJ-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'project_manager_id');
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(ProjectTeamMember::class);
    }

    public function projectUsers(): HasMany
    {
        return $this->hasMany(ProjectUser::class);
    }

    public function assignedUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_users')
            ->withPivot(['role', 'status', 'start_date', 'end_date', 'can_download_documents', 'can_upload_documents', 'can_approve', 'can_comment', 'can_view_financials', 'can_view_drawings'])
            ->withTimestamps();
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function materialAllocations(): HasMany
    {
        return $this->hasMany(MaterialAllocation::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function subcontractors()
    {
        return Subcontractor::query()
            ->whereIn('id', $this->contracts()->pluck('subcontractor_id')->unique())
            ->get();
    }

    public function teamSlot(string $slot): ?Staff
    {
        return $this->teamMembers->firstWhere('role_slot', $slot)?->employee;
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(ProjectBudget::class);
    }

    public function costCentres(): HasMany
    {
        return $this->hasMany(CostCentre::class);
    }

    public function boqItems(): HasMany
    {
        return $this->hasMany(BoqItem::class);
    }

    public function commitments(): HasMany
    {
        return $this->hasMany(Commitment::class);
    }

    public function changeOrders(): HasMany
    {
        return $this->hasMany(ChangeOrder::class);
    }

    public function progressClaims(): HasMany
    {
        return $this->hasMany(ProgressClaim::class);
    }

    public function paymentCertificates(): HasMany
    {
        return $this->hasMany(PaymentCertificate::class);
    }

    public function retentionEntries(): HasMany
    {
        return $this->hasMany(RetentionEntry::class);
    }

    public function cashFlowForecasts(): HasMany
    {
        return $this->hasMany(CashFlowForecast::class);
    }

    public function revenueRecognitions(): HasMany
    {
        return $this->hasMany(RevenueRecognition::class);
    }

    public function forecasts(): HasMany
    {
        return $this->hasMany(ProjectForecast::class);
    }
}
