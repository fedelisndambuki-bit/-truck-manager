<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrameworkAgreementItem extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'negotiated_price' => 'decimal:2',
        'volume_commitment' => 'decimal:2',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(SupplierContract::class, 'supplier_contract_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
