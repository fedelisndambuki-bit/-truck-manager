<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverWalletTransaction extends Model
{
    protected $guarded    = [];
    public    $incrementing = false;
    protected $keyType    = 'string';

    public function wallet()
    {
        return $this->belongsTo(DriverWallet::class, 'driver_wallet_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function formattedAmount(): string
    {
        $prefix = $this->isCredit() ? '+ ' : '- ';
        return $prefix . 'KES ' . number_format(abs($this->amount) / 100, 2);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'salary'     => 'Salary',
            'bonus'      => 'Bonus',
            'allowance'  => 'Allowance',
            'advance'    => 'Advance',
            'withdrawal' => 'Withdrawal',
            default      => ucfirst($this->type),
        };
    }
}
