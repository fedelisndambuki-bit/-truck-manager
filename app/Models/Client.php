<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'customer_since' => 'date',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Client $client) {
            if (empty($client->customer_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $client->customer_number = 'CUST-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function cargo(): HasMany
    {
        return $this->hasMany(Cargo::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_account_manager_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function outstandingBalance(): float
    {
        return (float) Invoice::where('client_id', $this->id)->where('status', '!=', 'paid')->sum('total_amount');
    }

    public function availableCredit(): ?float
    {
        if (! $this->credit_limit) {
            return null;
        }

        return max(0, (float) $this->credit_limit - $this->outstandingBalance());
    }
}
