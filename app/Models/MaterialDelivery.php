<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaterialDelivery extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'delivered_at' => 'date',
        'arrival_time' => 'datetime',
        'offloading_time' => 'datetime',
        'photos' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (MaterialDelivery $delivery) {
            if (empty($delivery->delivery_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $delivery->delivery_number = 'DEL-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'received_by_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceivedNote(): HasOne
    {
        return $this->hasOne(GoodsReceivedNote::class);
    }
}
