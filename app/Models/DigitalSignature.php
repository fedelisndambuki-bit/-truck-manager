<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DigitalSignature extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'signed_at' => 'datetime',
        'verified' => 'boolean',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (DigitalSignature $signature) {
            $signature->signed_at ??= now();
            $signature->signature_hash ??= hash('sha256', $signature->signable_type . $signature->signable_id . $signature->signer_staff_id . $signature->signed_at);
        });
    }

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'signer_staff_id');
    }
}
