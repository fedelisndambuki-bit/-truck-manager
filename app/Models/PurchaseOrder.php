<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    protected $casts = [
        'expected_delivery' => 'date',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (PurchaseOrder $order) {
            if (empty($order->po_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $order->po_number = 'PO-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }

            // The two legacy/new status columns are kept in sync so both the
            // old top-level PurchaseOrderResource (approval_status) and the
            // WorkflowEngine (which only reflects onto a column literally
            // named `status`) see a consistent value regardless of which
            // side wrote it.
            if (isset($order->status) && ! isset($order->approval_status)) {
                $order->approval_status = $order->status;
            } elseif (isset($order->approval_status) && ! isset($order->status)) {
                $order->status = $order->approval_status;
            }
        });

        static::saving(function (PurchaseOrder $order) {
            if ($order->isDirty('status') && ! $order->isDirty('approval_status')) {
                $order->approval_status = $order->status;
            } elseif ($order->isDirty('approval_status') && ! $order->isDirty('status')) {
                $order->status = $order->approval_status;
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(CostCentre::class);
    }

    public function requestForQuotation(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class);
    }

    public function supplierQuotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class);
    }

    public function blanketPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(BlanketPurchaseOrder::class);
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(MaterialDelivery::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ProcurementDocument::class, 'documentable');
    }

    /**
     * Recompute subtotal/total from line items. Called after the items
     * repeater saves (afterCreate/afterSave), mirroring the explicit
     * recalculation style used elsewhere (Material::recalculateStock()).
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('total');
        $total = $subtotal - (float) $this->discount_amount + (float) $this->tax_amount;

        $this->updateQuietly([
            'subtotal' => $subtotal,
            'total_amount' => $total,
        ]);
    }
}
