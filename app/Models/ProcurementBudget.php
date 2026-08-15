<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementBudget extends BaseModel
{
    protected $guarded = [];

    public const PERIOD_TYPES = ['Annual', 'Quarterly', 'Monthly'];

    public const STATUSES = ['Active', 'Closed'];

    protected $casts = [
        'approved_budget' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (ProcurementBudget $budget) {
            if (empty($budget->budget_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $budget->budget_number = 'PBG-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(CostCentre::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
