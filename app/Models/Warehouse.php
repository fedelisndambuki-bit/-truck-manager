<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends BaseModel
{
    protected $guarded = [];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'manager_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(MaterialDelivery::class);
    }

    public function getCurrentStockValueAttribute(): float
    {
        return (float) StockLevel::withoutGlobalScope('company')
            ->where('stock_levels.warehouse_id', $this->id)
            ->join('materials', 'materials.id', '=', 'stock_levels.material_id')
            ->selectRaw('sum(stock_levels.quantity_on_hand * coalesce(materials.average_cost, materials.unit_cost, 0)) as total')
            ->value('total') ?? 0;
    }
}
