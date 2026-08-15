<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends BaseModel
{
    protected $guarded = [];

    protected static function booted()
    {
        parent::booted();

        static::saving(function (PurchaseOrderItem $item) {
            $item->total = (float) $item->quantity * (float) $item->unit_price;
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
