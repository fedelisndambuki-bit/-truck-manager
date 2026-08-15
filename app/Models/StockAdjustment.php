<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends BaseModel
{
    protected $guarded = [];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }

    /**
     * Apply the adjustment delta to the warehouse's StockLevel.
     */
    public function approve(): void
    {
        $level = StockLevel::firstOrCreate(
            ['material_id' => $this->material_id, 'warehouse_id' => $this->warehouse_id, 'batch_number' => ''],
            ['company_id' => $this->company_id]
        );

        if ($this->adjustment_type === 'Positive') {
            $level->increment('quantity_on_hand', $this->quantity);
        } else {
            $level->decrement('quantity_on_hand', min($this->quantity, $level->quantity_on_hand));
        }

        $this->material->recalculateStock();

        InventoryMovement::record([
            'company_id' => $this->company_id,
            'movement_type' => 'Adjustment',
            'material_id' => $this->material_id,
            'warehouse_id' => $this->warehouse_id,
            'quantity' => $this->quantity,
            'operator_id' => $this->approved_by_id,
            'description' => "{$this->adjustment_type} adjustment ({$this->reason})",
        ], $this);

        $this->update(['status' => 'Approved']);
    }
}
