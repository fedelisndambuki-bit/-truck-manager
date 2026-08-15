<?php

namespace App\Models;

class DriverWallet extends BaseFinancialModel
{
    protected $table = 'driver_wallets';
    protected $guarded = [];

    protected $casts = [
        'balance' => 'integer',
        'version' => 'integer',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
