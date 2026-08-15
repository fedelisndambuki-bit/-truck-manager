<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionStockTransfer extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    public const STATUSES = ['Pending', 'Submitted', 'Approved', 'Completed', 'Rejected'];

    public const RECEIVED_STATUSES = ['Pending', 'Received'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (ProductionStockTransfer $transfer) {
            if (empty($transfer->transfer_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $transfer->transfer_number = 'TRF-Q-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function sourceStockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class, 'source_stockpile_id');
    }

    public function destinationStockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class, 'destination_stockpile_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'received_by_id');
    }

    /**
     * Move stock physically between two quarry Stockpiles.
     *
     * @throws \RuntimeException if there isn't enough available stock at the source.
     */
    public function complete(): void
    {
        $source = $this->sourceStockpile;

        if (! $source || $source->quantity_available < $this->quantity) {
            throw new \RuntimeException('Insufficient available stock at the source stockpile for this transfer.');
        }

        $source->decrement('quantity_available', $this->quantity);
        $this->destinationStockpile?->increment('quantity_available', $this->quantity);

        StockMovement::record([
            'company_id' => $this->company_id,
            'movement_type' => 'Transfer',
            'material_id' => $this->material_id,
            'quantity' => $this->quantity,
            'source_stockpile_id' => $this->source_stockpile_id,
            'destination_stockpile_id' => $this->destination_stockpile_id,
            'operator_id' => $this->operator_id,
            'vehicle_registration' => $this->vehicle_registration,
        ], $this);

        $this->update(['status' => 'Completed']);
    }
}
