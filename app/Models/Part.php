<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends BaseModel
{
    protected $fillable = [
        'company_id',
        'eam_store_id',
        'name',
        'part_number',
        'category',
        'unit_price',
        'stock_quantity',
        'reorder_level',
        'min_stock',
        'max_stock',
        'reserved_quantity',
        'bin_location',
        'supplier',
        'supplier_contact',
        'description',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'reorder_level' => 'integer',
        'min_stock' => 'integer',
        'max_stock' => 'integer',
        'reserved_quantity' => 'integer',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(PartUsage::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(EamStore::class, 'eam_store_id');
    }

    public function isBelowReorderLevel(): bool
    {
        return $this->stock_quantity <= $this->reorder_level;
    }
}
