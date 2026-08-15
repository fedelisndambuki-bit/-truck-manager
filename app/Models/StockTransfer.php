<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransfer extends BaseModel
{
    protected $guarded = [];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (StockTransfer $transfer) {
            if (empty($transfer->transfer_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $transfer->transfer_number = 'TRF-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function sourceProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'source_project_id');
    }

    public function sourceSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'source_site_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function destinationProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'destination_project_id');
    }

    public function destinationSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'destination_site_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }

    /**
     * Move stock physically from the source warehouse to the destination
     * warehouse. Project/Site fields are context tags for reporting only —
     * the real ledger key is (material, warehouse, batch).
     *
     * @throws \RuntimeException if there isn't enough available stock at the source.
     */
    public function approve(): void
    {
        $sourceLevel = StockLevel::where('material_id', $this->material_id)
            ->where('warehouse_id', $this->source_warehouse_id)
            ->where('batch_number', '')
            ->first();

        if (! $sourceLevel || $sourceLevel->available_quantity < $this->quantity) {
            throw new \RuntimeException('Insufficient available stock at the source warehouse for this transfer.');
        }

        $sourceLevel->decrement('quantity_on_hand', $this->quantity);

        $destinationLevel = StockLevel::firstOrCreate(
            [
                'material_id' => $this->material_id,
                'warehouse_id' => $this->destination_warehouse_id,
                'batch_number' => '',
            ],
            [
                'company_id' => $this->company_id,
                'project_id' => $this->destination_project_id,
                'site_id' => $this->destination_site_id,
            ]
        );
        $destinationLevel->increment('quantity_on_hand', $this->quantity);

        $this->material->recalculateStock();

        InventoryMovement::record([
            'company_id' => $this->company_id,
            'movement_type' => 'Transfer',
            'material_id' => $this->material_id,
            'warehouse_id' => $this->destination_warehouse_id,
            'quantity' => $this->quantity,
            'operator_id' => $this->approved_by_id,
            'description' => "Transfer {$this->transfer_number}: {$this->sourceWarehouse?->name} \u{2192} {$this->destinationWarehouse?->name}",
        ], $this);

        $this->update(['status' => 'Approved']);
    }
}
