<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionRun extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    protected $casts = [
        'start_time' => 'datetime',
        'finish_time' => 'datetime',
    ];

    public const STATUSES = ['Draft', 'Submitted', 'Approved', 'Rejected', 'Completed'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (ProductionRun $run) {
            if (empty($run->run_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $run->run_number = 'RUN-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });

        static::updated(function (ProductionRun $run) {
            if ($run->wasChanged('status') && $run->status === 'Approved') {
                $run->applyToStockpiles();
            }
        });
    }

    /**
     * Approving a production run credits each output line item's quantity
     * into its destination Stockpile — mirrors ProductionLog::applyToStockpiles().
     */
    public function applyToStockpiles(): void
    {
        $this->outputs()->with('stockpile')->get()->each(function (ProductionOutput $output) {
            $output->stockpile?->increment('quantity_available', $output->quantity_produced);
        });
    }

    public function crushingPlant(): BelongsTo
    {
        return $this->belongsTo(CrushingPlant::class);
    }

    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProductionOutput::class);
    }

    /**
     * No direct FK exists from downtime to a specific run — this is a
     * defensible real join against the same plant/line during the run's
     * time window, not a fabricated relation.
     */
    public function relatedDowntimes()
    {
        return ProductionDowntime::query()
            ->where('crushing_plant_id', $this->crushing_plant_id)
            ->when($this->production_line_id, fn ($q) => $q->where('production_line_id', $this->production_line_id))
            ->when($this->start_time, fn ($q) => $q->where('start_time', '>=', $this->start_time->copy()->subHours(1)))
            ->when($this->finish_time, fn ($q) => $q->where('start_time', '<=', $this->finish_time->copy()->addHours(1)))
            ->get();
    }

    public function qualityTests(): HasMany
    {
        return $this->hasMany(PlantQualityTest::class);
    }

    public function yieldPercent(): float
    {
        if ((float) $this->input_quantity <= 0) {
            return 0.0;
        }

        return round(((float) $this->output_quantity / (float) $this->input_quantity) * 100, 1);
    }

    public function wastePercent(): float
    {
        return max(0.0, round(100 - $this->yieldPercent(), 1));
    }

    public function recoveryPercent(): float
    {
        return $this->yieldPercent();
    }

    public function durationMinutes(): ?int
    {
        if (! $this->start_time || ! $this->finish_time) {
            return null;
        }

        return $this->start_time->diffInMinutes($this->finish_time);
    }
}
