<?php

namespace App\Models;

class GatewayPayment extends BaseFinancialModel
{
    protected $table = 'fin_payments'; // Fixed: Using consistent fin_ prefix
    protected $guarded = [];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
    ];
}
