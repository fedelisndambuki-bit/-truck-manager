<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SupplierInvoice extends BaseModel
{
    protected $guarded = [];

    public const MATCHING_STATUSES = ['Unmatched', 'Matched', 'Discrepancy'];

    public const STATUSES = ['Pending', 'Approved', 'Paid', 'Disputed'];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'match_discrepancies' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (SupplierInvoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $invoice->invoice_number = 'INV-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceivedNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierInvoiceItem::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ProcurementDocument::class, 'documentable');
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->get()->sum(fn (SupplierInvoiceItem $item) => $item->lineTotal());

        $this->updateQuietly([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal + (float) $this->tax_amount,
        ]);
    }
}
