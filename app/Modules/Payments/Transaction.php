<?php

namespace App\Modules\Payments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\MultiTenantScope;

class Transaction extends Model
{
    use HasFactory, MultiTenantScope;

    protected $guarded = [];

    public function settlementItem()
    {
        return $this->hasOne(\App\Modules\Settlement\SettlementItem::class);
    }
}
