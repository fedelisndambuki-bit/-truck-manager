<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialReturn extends BaseModel
{
    protected $table = 'material_returns';

    protected $guarded = [];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Credit the warehouse's StockLevel with the returned quantity. Damaged
     * returns are credited to damaged_quantity (still on-site, not usable)
     * rather than the usable quantity_on_hand.
     */
    public function approve(): void
    {
        $level = StockLevel::firstOrCreate(
            ['material_id' => $this->material_id, 'warehouse_id' => $this->warehouse_id, 'batch_number' => ''],
            ['company_id' => $this->company_id, 'project_id' => $this->project_id, 'site_id' => $this->site_id]
        );

        if ($this->condition === 'Damaged') {
            $level->increment('damaged_quantity', $this->return_quantity);
        } else {
            $level->increment('quantity_on_hand', $this->return_quantity);
        }

        $this->material->recalculateStock();

        InventoryMovement::record([
            'company_id' => $this->company_id,
            'movement_type' => 'Return',
            'material_id' => $this->material_id,
            'warehouse_id' => $this->warehouse_id,
            'quantity' => $this->return_quantity,
            'description' => "Return ({$this->condition})",
        ], $this);

        $this->update(['status' => 'Approved']);
    }
}
