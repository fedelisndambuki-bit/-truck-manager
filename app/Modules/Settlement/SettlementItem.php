<?php

namespace App\Modules\Settlement;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Payments\Transaction;

class SettlementItem extends Model
{
    protected $table = 'payment_settlement_items';
    protected $guarded = [];

    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
