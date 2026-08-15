<?php

namespace App\Models;

class LedgerEntry extends BaseFinancialModel
{
    protected $table = 'fin_ledger_entries';
    protected $guarded = [];

    protected $casts = [
        'debit' => 'integer',
        'credit' => 'integer',
    ];
}
