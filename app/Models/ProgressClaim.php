<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgressClaim extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'previous_certified_amount' => 'decimal:2',
        'work_completed_amount' => 'decimal:2',
        'amount_claimed' => 'decimal:2',
        'retention_percent' => 'decimal:2',
        'retention_amount' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'certified_amount' => 'decimal:2',
        'certified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (ProgressClaim $claim) {
            if (empty($claim->claim_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $claim->claim_number = 'PC-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });

        static::saving(function (ProgressClaim $claim) {
            $claim->calculate();
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'submitted_by_id');
    }

    public function certifiedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'certified_by_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function retentionEntries(): HasMany
    {
        return $this->hasMany(RetentionEntry::class, 'source_id')->where('source_type', 'progress_claim');
    }

    public function calculate(): void
    {
        $this->retention_amount = round((float) $this->amount_claimed * (float) $this->retention_percent / 100, 2);
        $taxable = (float) $this->amount_claimed - (float) $this->retention_amount;
        $this->vat_amount = round($taxable * (float) $this->vat_percent / 100, 2);
        $this->certified_amount = round($taxable + $this->vat_amount - (float) $this->previous_certified_amount, 2);
    }

    public function certify(): void
    {
        $this->update([
            'status' => 'Certified',
            'certified_at' => now(),
        ]);

        if ($this->retention_amount > 0) {
            RetentionEntry::updateOrCreate(
                ['source_type' => 'progress_claim', 'source_id' => $this->id],
                [
                    'company_id' => $this->company_id,
                    'project_id' => $this->project_id,
                    'percentage' => $this->retention_percent,
                    'held_amount' => $this->retention_amount,
                    'release_status' => 'Held',
                ]
            );
        }
    }
}
