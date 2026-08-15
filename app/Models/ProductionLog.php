<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionLog extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public const STATUSES = ['Draft', 'Submitted', 'Approved', 'Closed'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (ProductionLog $log) {
            if (empty($log->production_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $log->production_number = 'PROD-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });

        static::updated(function (ProductionLog $log) {
            if ($log->wasChanged('status') && $log->status === 'Approved') {
                $log->applyToStockpiles();
            }
        });
    }

    /**
     * Approving a production record is what actually credits stock — each
     * output line item's quantity is posted into its destination Stockpile,
     * mirroring LeaveRequest::applyToBalance()'s "approval has a real
     * side-effect" pattern.
     */
    public function applyToStockpiles(): void
    {
        $this->outputs()->with('stockpile')->get()->each(function (ProductionOutput $output) {
            $output->stockpile?->increment('quantity_available', $output->quantity_produced);
        });
    }

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function quarryFace(): BelongsTo
    {
        return $this->belongsTo(QuarryFace::class);
    }

    public function pit(): BelongsTo
    {
        return $this->belongsTo(Pit::class);
    }

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function crushingPlant(): BelongsTo
    {
        return $this->belongsTo(CrushingPlant::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'logged_by_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProductionOutput::class);
    }

    public function downtimes(): HasMany
    {
        return $this->hasMany(ProductionDowntime::class);
    }
}
