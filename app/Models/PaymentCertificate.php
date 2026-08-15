<?php

namespace App\Models;

use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentCertificate extends BaseModel
{
    use ProjectAccessScope;

    protected $guarded = [];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'retention_amount' => 'decimal:2',
        'advance_recovery_amount' => 'decimal:2',
        'previous_certified_amount' => 'decimal:2',
        'net_amount_due' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (PaymentCertificate $certificate) {
            if (empty($certificate->certificate_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $certificate->certificate_number = 'PC-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });

        static::saving(function (PaymentCertificate $certificate) {
            $certificate->calculate();
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function progressClaim(): BelongsTo
    {
        return $this->belongsTo(ProgressClaim::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function calculate(): void
    {
        $retentionPercent = (float) ($this->contract?->retention_percent ?? $this->progressClaim?->retention_percent ?? 0);
        $this->retention_amount = round((float) $this->gross_amount * $retentionPercent / 100, 2);
        $this->net_amount_due = round(
            (float) $this->gross_amount
            - (float) $this->retention_amount
            - (float) $this->advance_recovery_amount
            - (float) $this->previous_certified_amount,
            2
        );
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return round((float) $this->approved_amount - (float) $this->paid_amount, 2);
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'Approved',
            'approved_amount' => $this->net_amount_due,
            'approved_at' => now(),
        ]);

        $projectId = $this->project_id ?: $this->contract?->project_id;

        if ($projectId && $this->retention_amount > 0) {
            RetentionEntry::updateOrCreate(
                ['source_type' => 'payment_certificate', 'source_id' => $this->id],
                [
                    'company_id' => $this->company_id,
                    'project_id' => $projectId,
                    'percentage' => $this->contract?->retention_percent ?? $this->progressClaim?->retention_percent ?? 0,
                    'held_amount' => $this->retention_amount,
                    'release_status' => 'Held',
                ]
            );
        }
    }

    public function markPaid(): void
    {
        $this->update([
            'status' => 'Paid',
            'paid_amount' => $this->approved_amount,
        ]);
    }
}
