<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::saving(function (QuotationItem $item) {
            $gross = (float) $item->quantity * (float) $item->unit_price;
            $afterDiscount = $gross * (1 - ((float) $item->discount_percent / 100));
            $item->line_total = round($afterDiscount * (1 + ((float) $item->tax_percent / 100)), 2);
        });

        static::saved(function (QuotationItem $item) {
            $item->quotation?->recalculateTotals();
        });

        static::deleted(function (QuotationItem $item) {
            $item->quotation?->recalculateTotals();
        });
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
