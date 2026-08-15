<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePlan extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    public const PERIOD_TYPES = ['Annual', 'Quarterly', 'Monthly', 'Project-Based'];

    public const PRIORITIES = ['Low', 'Medium', 'High'];

    public const STATUSES = ['Draft', 'Pending', 'Approved', 'Rejected'];

    protected $casts = [
        'budget' => 'decimal:2',
        'estimated_quantity' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (PurchasePlan $plan) {
            if (empty($plan->plan_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $plan->plan_number = 'PP-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Pre-fill a new PurchaseRequest header from this approved plan — a
     * button the user triggers, not an automatic cron.
     */
    public function generateRequisition(): PurchaseRequest
    {
        return PurchaseRequest::create([
            'company_id' => $this->company_id,
            'business_unit_id' => $this->business_unit_id,
            'department_id' => $this->department_id,
            'project_id' => $this->project_id,
            'extraction_site_id' => $this->extraction_site_id,
            'warehouse_id' => $this->warehouse_id,
            'expected_cost' => $this->estimated_cost,
            'reason' => "Generated from Purchase Plan {$this->plan_number} ({$this->category})",
            'priority' => $this->priority,
            'status' => 'Pending',
            'source' => 'Manual',
        ]);
    }
}
