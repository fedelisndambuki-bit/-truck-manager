<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkshopBay extends BaseModel
{
    protected $guarded = [];

    public const STATUSES = ['Available', 'Occupied', 'Out of Service'];

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class);
    }

    public function currentWorkOrder(): ?MaintenanceWorkOrder
    {
        return $this->workOrders()->whereNotIn('status', ['Closed'])->latest('created_at')->first();
    }
}
