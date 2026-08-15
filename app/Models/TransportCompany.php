<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportCompany extends BaseModel
{
    protected $guarded = [];

    public const STATUSES = ['Active', 'Inactive'];

    public function dispatchOrders(): HasMany
    {
        return $this->hasMany(DispatchOrder::class);
    }
}
