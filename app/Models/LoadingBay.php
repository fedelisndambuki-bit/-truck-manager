<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoadingBay extends BaseModel
{
    protected $guarded = [];

    public const STATUSES = ['Available', 'Busy', 'Maintenance', 'Closed'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (LoadingBay $bay) {
            if (empty($bay->bay_code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $bay->bay_code = 'BAY-' . str_pad((string) ($lastId + 1), 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function stockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class);
    }

    public function loaderEquipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'loader_equipment_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }

    public function loadingInstructions(): HasMany
    {
        return $this->hasMany(LoadingInstruction::class);
    }

    public function currentTruck(): ?DispatchOrder
    {
        $instruction = $this->loadingInstructions()
            ->whereIn('status', ['Accepted', 'Loading', 'Paused'])
            ->latest('id')
            ->first();

        return $instruction?->dispatchOrder;
    }

    /**
     * Trucks currently queued or being loaded at this bay — mirrors
     * Weighbridge::currentQueueCount()'s active-status counting approach.
     */
    public function currentQueueCount(): int
    {
        return $this->loadingInstructions()
            ->whereIn('status', ['Waiting', 'Accepted', 'Loading', 'Paused'])
            ->count();
    }

    public function averageLoadingTimeMinutes(): float
    {
        $instructions = $this->loadingInstructions()
            ->where('status', 'Completed')
            ->whereNotNull('loading_started_at')
            ->whereNotNull('loading_completed_at')
            ->get();

        if ($instructions->isEmpty()) {
            return 0.0;
        }

        return round((float) $instructions->avg(
            fn (LoadingInstruction $i) => $i->loading_started_at->diffInMinutes($i->loading_completed_at)
        ), 1);
    }
}
