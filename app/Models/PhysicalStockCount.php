<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhysicalStockCount extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'scheduled_date' => 'date',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (PhysicalStockCount $count) {
            if (empty($count->count_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $count->count_number = 'PSC-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PhysicalStockCountItem::class);
    }

    /**
     * Populate count items from every material currently stocked in this
     * warehouse, snapshotting the expected quantity from the live ledger.
     */
    public function populateFromCurrentStock(): void
    {
        $levels = StockLevel::where('warehouse_id', $this->warehouse_id)->get();

        foreach ($levels as $level) {
            PhysicalStockCountItem::firstOrCreate(
                ['physical_stock_count_id' => $this->id, 'material_id' => $level->material_id],
                ['company_id' => $this->company_id, 'expected_quantity' => $level->quantity_on_hand]
            );
        }

        $this->update(['status' => 'In Progress']);
    }

    /**
     * Reconcile every counted item against the ledger: set the StockLevel to
     * the counted value and create + approve a StockAdjustment audit record
     * for any variance found.
     */
    public function complete(): void
    {
        foreach ($this->items()->whereNotNull('counted_quantity')->get() as $item) {
            $variance = (float) $item->counted_quantity - (float) $item->expected_quantity;
            $item->update(['variance' => $variance]);

            if (abs($variance) > 0.001) {
                $adjustment = StockAdjustment::create([
                    'company_id' => $this->company_id,
                    'material_id' => $item->material_id,
                    'warehouse_id' => $this->warehouse_id,
                    'adjustment_type' => $variance > 0 ? 'Positive' : 'Negative',
                    'reason' => 'Correction',
                    'quantity' => abs($variance),
                    'source' => 'PhysicalCount',
                ]);
                $adjustment->approve();

                $item->update(['adjustment_created' => true]);
            }
        }

        $this->update(['status' => 'Completed']);
    }
}
