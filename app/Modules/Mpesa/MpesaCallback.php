<?php

namespace App\Modules\Mpesa;

use Illuminate\Database\Eloquent\Model;

class MpesaCallback extends Model
{
    protected $guarded = [];

    protected $casts = [
        'raw_payload' => 'array',
        'transaction_date' => 'datetime',
    ];
}
