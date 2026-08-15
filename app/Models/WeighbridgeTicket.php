<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeighbridgeTicket extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'gate_in_at' => 'datetime',
        'gate_out_at' => 'datetime',
        'is_manual_override' => 'boolean',
    ];

    public const DIRECTIONS = ['Inbound', 'Outbound'];

    public const STATUSES = ['Pending', 'Weighed', 'Rejected'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (WeighbridgeTicket $ticket) {
            if (empty($ticket->ticket_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $ticket->ticket_number = 'WB-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if ($ticket->gross_weight !== null && $ticket->tare_weight !== null && empty($ticket->net_weight)) {
                $ticket->net_weight = $ticket->gross_weight - $ticket->tare_weight;
            }
        });

        static::updating(function (WeighbridgeTicket $ticket) {
            if ($ticket->gross_weight !== null && $ticket->tare_weight !== null) {
                $ticket->net_weight = $ticket->gross_weight - $ticket->tare_weight;
            }
        });

        static::updated(function (WeighbridgeTicket $ticket) {
            if (
                $ticket->wasChanged('status')
                && $ticket->status === 'Weighed'
                && $ticket->direction === 'Outbound'
                && $ticket->stockpile_id
                && ! StockMovement::query()->where('reference_type', static::class)->where('reference_id', $ticket->id)->exists()
            ) {
                $stockpile = $ticket->stockpile;
                $stockpile?->decrement('quantity_available', (float) $ticket->net_weight);

                StockMovement::record([
                    'company_id' => $ticket->company_id,
                    'movement_type' => 'Dispatch',
                    'material_id' => $ticket->material_id,
                    'quantity' => $ticket->net_weight,
                    'source_stockpile_id' => $ticket->stockpile_id,
                    'operator_id' => $ticket->operator_id,
                    'vehicle_registration' => $ticket->vehicle_registration,
                ], $ticket);
            }
        });
    }

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function stockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }

    public function turnaroundMinutes(): ?int
    {
        if (! $this->gate_in_at || ! $this->gate_out_at) {
            return null;
        }

        return $this->gate_in_at->diffInMinutes($this->gate_out_at);
    }
}
