<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SupplierContract extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    public const TYPES = ['Standard', 'Framework Agreement', 'Service Agreement'];

    public const STATUSES = ['Draft', 'Active', 'Expired', 'Terminated', 'Renewed'];

    protected $casts = [
        'contract_value' => 'decimal:2',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'renewal_date' => 'date',
        'service_levels' => 'array',
        'kpis' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (SupplierContract $contract) {
            if (empty($contract->contract_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $contract->contract_number = 'SC-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FrameworkAgreementItem::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ProcurementDocument::class, 'documentable');
    }

    public function isExpiringSoon(int $withinDays = 30): bool
    {
        return $this->expiry_date && $this->status === 'Active'
            && now()->diffInDays($this->expiry_date, false) <= $withinDays
            && now()->diffInDays($this->expiry_date, false) >= 0;
    }
}
