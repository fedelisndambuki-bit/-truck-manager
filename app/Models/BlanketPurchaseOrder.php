<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlanketPurchaseOrder extends BaseModel
{
    protected $guarded = [];

    public const STATUSES = ['Active', 'Suspended', 'Expired', 'Closed'];

    protected $casts = [
        'maximum_value' => 'decimal:2',
        'maximum_quantity' => 'decimal:2',
        'released_value' => 'decimal:2',
        'released_quantity' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (BlanketPurchaseOrder $bpo) {
            if (empty($bpo->bpo_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $bpo->bpo_number = 'BPO-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function releaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function remainingValue(): float
    {
        return round((float) $this->maximum_value - (float) $this->released_value, 2);
    }

    public function remainingQuantity(): float
    {
        return round((float) $this->maximum_quantity - (float) $this->released_quantity, 2);
    }

    /**
     * Called after a release Purchase Order against this blanket order is
     * created/updated — keeps the released running totals in sync.
     */
    public function recalculateReleased(): void
    {
        $this->updateQuietly([
            'released_value' => (float) $this->releaseOrders()->sum('total_amount'),
            'released_quantity' => (float) $this->releaseOrders()->with('items')->get()->flatMap->items->sum('quantity'),
        ]);
    }
}
