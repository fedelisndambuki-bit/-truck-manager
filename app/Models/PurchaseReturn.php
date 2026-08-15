<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseReturn extends BaseModel
{
    protected $guarded = [];

    public const REASONS = ['Damaged', 'Wrong Item', 'Over Delivery', 'Under Delivery', 'Expired', 'Quality Failure'];

    public const REPLACEMENT_STATUSES = ['Pending', 'Replaced', 'Credited', 'Not Applicable'];

    public const STATUSES = ['Draft', 'Submitted', 'Approved', 'Rejected', 'Closed'];

    protected $casts = [
        'photos' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (PurchaseReturn $return) {
            if (empty($return->return_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $return->return_number = 'RTV-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
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
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ProcurementDocument::class, 'documentable');
    }
}
