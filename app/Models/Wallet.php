<?php

namespace App\Models;

class Wallet extends BaseFinancialModel
{
    protected $table = 'fin_wallets';
    protected $guarded = [];

    protected $casts = [
        'ledger_balance' => 'integer',
        'available_balance' => 'integer',
        'pending_holds' => 'integer',
    ];
}
