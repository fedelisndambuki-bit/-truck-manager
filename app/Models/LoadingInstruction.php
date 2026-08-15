<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class LoadingInstruction extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'generated_at' => 'datetime',
        'accepted_at' => 'datetime',
        'loading_started_at' => 'datetime',
        'paused_at' => 'datetime',
        'loading_completed_at' => 'datetime',
    ];

    public const STATUSES = ['Waiting', 'Accepted', 'Loading', 'Paused', 'Completed', 'Rejected', 'Cancelled'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (LoadingInstruction $instruction) {
            if (empty($instruction->loading_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $instruction->loading_number = 'LI-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });

        static::created(function (LoadingInstruction $instruction) {
            if (empty($instruction->qr_code)) {
                try {
                    $url = route('filament.admin.resources.loading-instructions.view', ['record' => $instruction->id]);
                } catch (\Throwable $e) {
                    $url = $instruction->loading_number;
                }

                $instruction->qr_code = QrCode::size(200)->generate($url);
                $instruction->saveQuietly();
            }
        });
    }

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }

    public function loadingBay(): BelongsTo
    {
        return $this->belongsTo(LoadingBay::class);
    }

    public function loaderEquipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'loader_equipment_id');
    }

    public function loaderOperator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'loader_operator_id');
    }

    public function stockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class);
    }

    public function accept(): void
    {
        $this->update(['status' => 'Accepted', 'accepted_at' => now()]);
    }

    public function startLoading(): void
    {
        $this->update(['status' => 'Loading', 'loading_started_at' => now()]);
        $this->dispatchOrder?->update(['status' => 'Loading', 'loading_started_at' => $this->loading_started_at]);
    }

    public function pause(): void
    {
        $this->update(['status' => 'Paused', 'paused_at' => now()]);
    }

    public function resume(): void
    {
        $this->update(['status' => 'Loading']);
    }

    public function complete(?float $estimatedQuantityLoaded = null): void
    {
        $this->update([
            'status' => 'Completed',
            'loading_completed_at' => now(),
            'estimated_quantity_loaded' => $estimatedQuantityLoaded ?? $this->estimated_quantity_loaded,
        ]);

        $this->dispatchOrder?->update([
            'status' => 'Awaiting Gross Weight',
            'loading_completed_at' => $this->loading_completed_at,
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update(['status' => 'Rejected', 'rejected_reason' => $reason]);
    }
}
