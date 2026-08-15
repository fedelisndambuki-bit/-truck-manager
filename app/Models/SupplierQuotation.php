<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SupplierQuotation extends BaseModel
{
    protected $guarded = [];

    public const STATUSES = ['Submitted', 'Under Review', 'Accepted', 'Rejected'];

    protected $casts = [
        'validity_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (SupplierQuotation $quotation) {
            if (empty($quotation->quotation_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $quotation->quotation_number = 'QT-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function requestForQuotation(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierQuotationItem::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ProcurementDocument::class, 'documentable');
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->get()->sum(fn (SupplierQuotationItem $item) => $item->lineTotal());
        $taxAmount = (float) $this->items()->get()->sum(fn (SupplierQuotationItem $item) => $item->lineTotal() * ((float) $item->tax_percent / 100));

        $this->updateQuietly([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
        ]);
    }
}
