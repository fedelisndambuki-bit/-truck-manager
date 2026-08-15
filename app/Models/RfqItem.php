<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfqItem extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function requestForQuotation(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(SupplierQuotationItem::class);
    }
}
