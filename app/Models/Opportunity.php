<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opportunity extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'expected_close_date' => 'date',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Opportunity $opportunity) {
            if (empty($opportunity->opportunity_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $opportunity->opportunity_number = 'OPP-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to_id');
    }

    public function isOpen(): bool
    {
        return ! in_array($this->stage, ['Won', 'Lost'], true);
    }
}
