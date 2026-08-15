<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierQuotationItem extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::saving(function (SupplierQuotationItem $item) {
            $item->total = $item->lineTotal();
        });
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class, 'supplier_quotation_id');
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RfqItem::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * quantity * unit_price, net of the line discount — kept as a plain
     * method (not an accessor) since "total" is a real column and an
     * accessor of the same name would shadow raw SQL aggregates on it
     * (see PurchaseRequest::totalEstimatedCost()'s note on this exact trap).
     */
    public function lineTotal(): float
    {
        $gross = (float) $this->quantity * (float) $this->unit_price;

        return round($gross - ($gross * (float) $this->discount_percent / 100), 2);
    }
}
