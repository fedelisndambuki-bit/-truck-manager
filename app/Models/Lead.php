<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Lead $lead) {
            if (empty($lead->lead_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $lead->lead_number = 'LEAD-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to_id');
    }

    public function convertedClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'converted_client_id');
    }

    /**
     * Converts this lead into a Client — the single Customer entity used
     * throughout Invoicing/Orders/Dispatch. firstOrCreate on company_name
     * since clients.company_name is unique, so re-converting a lead for an
     * already-known company reuses that Client rather than erroring.
     */
    public function convertToClient(): Client
    {
        $client = Client::withoutGlobalScope('company')->firstOrCreate(
            ['company_name' => $this->company_name],
            [
                'company_id' => $this->company_id,
                'contact_person' => $this->contact_person ?: $this->company_name,
                'phone' => $this->phone ?: '',
                'email' => $this->email,
                'address' => $this->address,
                'industry' => $this->industry,
                'customer_since' => now(),
            ]
        );

        $this->update([
            'status' => 'Converted',
            'converted_client_id' => $client->id,
            'converted_at' => now(),
        ]);

        return $client;
    }
}
