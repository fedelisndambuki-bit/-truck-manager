<?php

namespace App\Modules\Settlement;

use Illuminate\Database\Eloquent\Model;
use App\Traits\MultiTenantScope;

class Settlement extends Model
{
    use MultiTenantScope;

    protected $table = 'payment_settlements';
    protected $guarded = [];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SettlementItem::class);
    }
}
