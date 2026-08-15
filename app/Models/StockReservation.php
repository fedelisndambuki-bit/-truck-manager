<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends BaseModel
{
    protected $table = 'stockpile_reservations';

    protected $guarded = [];

    protected $casts = [
        'reservation_date' => 'date',
        'expiry_date' => 'date',
    ];

    public const STATUSES = ['Reserved', 'Released', 'Converted to Dispatch', 'Expired'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (StockReservation $reservation) {
            if (empty($reservation->reservation_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $reservation->reservation_number = 'RES-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });

        static::created(function (StockReservation $reservation) {
            if ($reservation->status === 'Reserved') {
                $reservation->stockpile?->increment('quantity_reserved', $reservation->quantity_reserved);
            }
        });
    }

    public function stockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function reservedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reserved_by_id');
    }

    public function release(): void
    {
        $this->stockpile?->decrement('quantity_reserved', $this->quantity_reserved);
        $this->update(['status' => 'Released']);
    }

    public function markExpired(): void
    {
        $this->stockpile?->decrement('quantity_reserved', $this->quantity_reserved);
        $this->update(['status' => 'Expired']);
    }

    public function convertToDispatch(): void
    {
        $stockpile = $this->stockpile;

        $stockpile?->decrement('quantity_reserved', $this->quantity_reserved);
        $stockpile?->decrement('quantity_available', $this->quantity_reserved);

        StockMovement::record([
            'company_id' => $this->company_id,
            'movement_type' => 'Dispatch',
            'material_id' => $stockpile?->material_id,
            'quantity' => $this->quantity_reserved,
            'source_stockpile_id' => $this->stockpile_id,
        ], $this);

        $this->update(['status' => 'Converted to Dispatch']);
    }
}
