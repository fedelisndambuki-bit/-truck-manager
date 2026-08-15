<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RequestForQuotation extends BaseModel
{
    protected $guarded = [];

    public const STATUSES = ['Draft', 'Open', 'Closed', 'Awarded', 'Cancelled'];

    protected $casts = [
        'closing_date' => 'date',
        'delivery_date' => 'date',
        'required_documents' => 'array',
        'evaluation_criteria' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (RequestForQuotation $rfq) {
            if (empty($rfq->rfq_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $rfq->rfq_number = 'RFQ-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class, 'request_for_quotation_id');
    }

    public function invitedSuppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'rfq_suppliers', 'request_for_quotation_id', 'supplier_id')
            ->withPivot(['invited_at', 'responded_at', 'status'])
            ->withTimestamps();
    }

    public function rfqSuppliers(): HasMany
    {
        return $this->hasMany(RfqSupplier::class, 'request_for_quotation_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class, 'request_for_quotation_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ProcurementDocument::class, 'documentable');
    }

    public function isClosingSoon(int $withinDays = 2): bool
    {
        return $this->closing_date && $this->status === 'Open'
            && now()->diffInDays($this->closing_date, false) <= $withinDays
            && now()->diffInDays($this->closing_date, false) >= 0;
    }

    /**
     * Award this RFQ to one quotation: generates a PurchaseOrder + its line
     * items from the winning SupplierQuotationItem rows, marks the winner
     * Accepted and every other quotation on this RFQ Rejected, and closes
     * the RFQ as Awarded.
     */
    public function awardTo(SupplierQuotation $quotation): PurchaseOrder
    {
        $order = PurchaseOrder::create([
            'company_id' => $this->company_id,
            'supplier_id' => $quotation->supplier_id,
            'project_id' => $this->purchaseRequest?->project_id,
            'currency' => $quotation->currency,
            'payment_terms' => $quotation->payment_terms,
            'expected_delivery' => $this->delivery_date,
            'purchase_request_id' => $this->purchase_request_id,
            'approval_status' => 'Draft',
        ]);

        foreach ($quotation->items as $item) {
            $order->items()->create([
                'company_id' => $this->company_id,
                'material_id' => $item->material_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->lineTotal(),
            ]);
        }

        $order->recalculateTotals();

        $quotation->update(['status' => 'Accepted']);
        $this->quotations()->where('id', '!=', $quotation->id)->update(['status' => 'Rejected']);
        $this->update(['status' => 'Awarded']);

        return $order;
    }
}
