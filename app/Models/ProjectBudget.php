<?php

namespace App\Models;

use App\Services\Construction\ProjectFinanceService;
use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBudget extends BaseModel
{
    use ProjectAccessScope;

    protected $guarded = [];

    protected $casts = [
        'approved_budget' => 'decimal:2',
        'revised_budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'last_synced_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (ProjectBudget $budget) {
            if (empty($budget->budget_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $budget->budget_number = 'BUD-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
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

    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(CostCentre::class);
    }

    public function costCode(): BelongsTo
    {
        return $this->belongsTo(CostCode::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }

    public function getEffectiveBudgetAttribute(): float
    {
        return (float) ($this->revised_budget > 0 ? $this->revised_budget : $this->approved_budget);
    }

    public function getVarianceAttribute(): float
    {
        return round($this->effective_budget - (float) $this->actual_cost, 2);
    }

    public function getRemainingBudgetAttribute(): float
    {
        return max(0, $this->variance);
    }

    public function recalculateActual(): void
    {
        $service = app(ProjectFinanceService::class);

        $this->actual_cost = $service->costForCostCode($this->project, $this->cost_code_id, $this->category);
        $this->last_synced_at = now();
        $this->saveQuietly();
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'Approved',
            'approved_at' => now(),
        ]);
    }
}
