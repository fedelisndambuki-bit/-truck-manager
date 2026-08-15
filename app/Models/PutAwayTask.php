<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PutAwayTask extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (PutAwayTask $task) {
            if (empty($task->task_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $task->task_number = 'PA-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function goodsReceivedNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function recommendedBin(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class, 'recommended_bin_id');
    }

    public function actualBin(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class, 'actual_bin_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'completed_by_id');
    }

    /**
     * Physically confirm the put-away: credits the bin's stock breakdown,
     * logs the movement, and closes the task. Doesn't touch StockLevel —
     * the material was already credited there when the GRN was approved;
     * this only records *where within the warehouse* it ended up.
     */
    public function complete(BinLocation $bin, ?Staff $by = null): void
    {
        BinStock::firstOrCreate(
            ['bin_location_id' => $bin->id, 'material_id' => $this->material_id, 'batch_number' => ''],
            ['company_id' => $this->company_id]
        )->increment('quantity', $this->quantity);

        InventoryMovement::record([
            'company_id' => $this->company_id,
            'movement_type' => 'PutAway',
            'material_id' => $this->material_id,
            'warehouse_id' => $this->warehouse_id,
            'destination_bin_id' => $bin->id,
            'quantity' => $this->quantity,
            'operator_id' => $by?->id,
            'description' => "Put-away {$this->task_number} completed",
        ], $this);

        $this->update([
            'actual_bin_id' => $bin->id,
            'status' => 'Completed',
            'completed_by_id' => $by?->id,
            'completed_at' => now(),
        ]);
    }
}
