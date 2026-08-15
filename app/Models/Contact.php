<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'is_decision_maker' => 'boolean',
        'is_technical_contact' => 'boolean',
        'is_finance_contact' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
