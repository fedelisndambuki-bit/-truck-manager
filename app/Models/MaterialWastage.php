<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialWastage extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'photos' => 'array',
    ];

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

    public function getWastePercentageAttribute(): ?float
    {
        $allocated = MaterialAllocation::where('material_id', $this->material_id)
            ->when($this->project_id, fn ($q) => $q->where('project_id', $this->project_id))
            ->sum('allocated_quantity');

        return $allocated > 0 ? round(((float) $this->quantity / (float) $allocated) * 100, 2) : null;
    }

    /**
     * Debit stock for the wasted quantity, if a warehouse was specified.
     */
    public function approve(): void
    {
        if ($this->warehouse_id) {
            $level = StockLevel::where('material_id', $this->material_id)
                ->where('warehouse_id', $this->warehouse_id)
                ->where('batch_number', '')
                ->first();
            $level?->decrement('quantity_on_hand', $this->quantity);
        }

        $this->material->recalculateStock();

        $this->update(['status' => 'Approved']);
    }
}
