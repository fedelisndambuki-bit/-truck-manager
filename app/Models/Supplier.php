<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends BaseModel
{
    protected $guarded = [];

    public const CATEGORIES = ['Materials', 'Equipment', 'Services', 'Consumables', 'Fuel', 'Spare Parts', 'Other'];

    public const RISK_RATINGS = ['Low', 'Medium', 'High'];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'insurance_expiry' => 'date',
        'performance_rating' => 'decimal:1',
        'delivery_performance_rating' => 'decimal:1',
        'quality_rating' => 'decimal:1',
        'credit_limit' => 'decimal:2',
        'certificates' => 'array',
        'licenses' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Supplier $supplier) {
            if (empty($supplier->supplier_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $supplier->supplier_number = 'SUP-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(SupplierAddress::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(SupplierBankAccount::class);
    }

    public function primaryContact(): ?SupplierContact
    {
        return $this->contacts()->where('is_primary', true)->first() ?? $this->contacts()->first();
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class, 'preferred_supplier_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(MaterialDelivery::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(SupplierPerformanceReview::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(SupplierContract::class);
    }

    public function blanketPurchaseOrders(): HasMany
    {
        return $this->hasMany(BlanketPurchaseOrder::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class);
    }

    public function rfqInvitations(): HasMany
    {
        return $this->hasMany(RfqSupplier::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
