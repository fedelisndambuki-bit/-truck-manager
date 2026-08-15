<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Weighbridge extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'calibration_due_date' => 'date',
        'certification_expiry' => 'date',
    ];

    public const SCALE_TYPES = ['Inbound', 'Outbound', 'Dual Direction'];

    public const STATUSES = ['Online', 'Offline', 'Maintenance'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Weighbridge $weighbridge) {
            if (empty($weighbridge->code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $weighbridge->code = 'WBM-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function currentOperator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'current_operator_id');
    }

    public function dispatchOrders(): HasMany
    {
        return $this->hasMany(DispatchOrder::class);
    }

    /**
     * Trucks currently occupying this weighbridge's queue — active statuses
     * that haven't reached Approved/Rejected/Exited/Cancelled yet.
     */
    public function currentQueueCount(): int
    {
        return $this->dispatchOrders()
            ->whereNotIn('status', ['Approved', 'Rejected', 'Exited', 'Cancelled'])
            ->count();
    }

    public function averageProcessingTimeMinutes(): float
    {
        $orders = $this->dispatchOrders()
            ->whereNotNull('gate_in_at')
            ->whereNotNull('exit_at')
            ->get();

        if ($orders->isEmpty()) {
            return 0.0;
        }

        return round((float) $orders->avg(fn (DispatchOrder $o) => $o->gate_in_at->diffInMinutes($o->exit_at)), 1);
    }
}
