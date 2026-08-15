<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletFunding extends BaseFinancialModel
{
    protected $table = 'fin_wallet_fundings';
    protected $guarded = [];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function fundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'funded_by_user_id');
    }
}
