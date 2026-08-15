<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class Material extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'standard_cost' => 'decimal:2',
        'average_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'max_stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'quantity_on_hand' => 'decimal:2',
        'images' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Material $material) {
            if (empty($material->material_code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $material->material_code = 'MAT-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });

        static::created(function (Material $material) {
            if (empty($material->qr_code)) {
                $material->qr_code = QrCode::size(200)->generate($material->material_code);
                $material->saveQuietly();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'subcategory_id');
    }

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(MaterialDelivery::class);
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(MaterialAllocation::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(MaterialConsumption::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(MaterialReturn::class);
    }

    public function wastages(): HasMany
    {
        return $this->hasMany(MaterialWastage::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Recalculate the cached quantity_on_hand rollup from the real
     * StockLevel ledger. Called explicitly at the end of every
     * stock-mutating action rather than kept as a live accessor.
     */
    public function recalculateStock(): void
    {
        $total = $this->stockLevels()->sum('quantity_on_hand');
        $this->updateQuietly(['quantity_on_hand' => $total]);
    }

    public function getInventoryValueAttribute(): float
    {
        return (float) $this->quantity_on_hand * (float) ($this->average_cost ?? $this->unit_cost ?? 0);
    }

    public function getStockStatusAttribute(): string
    {
        $onHand = (float) $this->quantity_on_hand;
        $reorder = (float) ($this->reorder_level ?? 0);

        if ($onHand <= 0) {
            return 'Out of Stock';
        }

        if ($reorder > 0 && $onHand <= $reorder) {
            return 'Low Stock';
        }

        return 'In Stock';
    }
}
