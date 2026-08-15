<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialConsumption extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (MaterialConsumption $consumption) {
            if (empty($consumption->cost)) {
                $material = Material::find($consumption->material_id);
                $rate = (float) ($material?->average_cost ?? $material?->unit_cost ?? 0);
                $consumption->cost = (float) $consumption->quantity_used * $rate;
            }
        });

        static::created(function (MaterialConsumption $consumption) {
            if ($consumption->warehouse_id) {
                $level = StockLevel::where('material_id', $consumption->material_id)
                    ->where('warehouse_id', $consumption->warehouse_id)
                    ->where('batch_number', '')
                    ->first();
                $level?->decrement('quantity_on_hand', $consumption->quantity_used);

                InventoryMovement::record([
                    'company_id' => $consumption->company_id,
                    'movement_type' => 'Consumption',
                    'material_id' => $consumption->material_id,
                    'warehouse_id' => $consumption->warehouse_id,
                    'quantity' => $consumption->quantity_used,
                    'operator_id' => $consumption->issued_by_id,
                    'description' => 'Material consumption',
                ], $consumption);
            }

            if ($consumption->allocation_id) {
                MaterialAllocation::where('id', $consumption->allocation_id)
                    ->increment('consumed_quantity', $consumption->quantity_used);
            }

            $consumption->material->recalculateStock();
        });
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(MaterialAllocation::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'issued_by_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'received_by_id');
    }
}
