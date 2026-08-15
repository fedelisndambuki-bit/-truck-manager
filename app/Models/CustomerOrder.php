<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CustomerOrder extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'requested_delivery_date' => 'date',
    ];

    public const STATUSES = ['Draft', 'Confirmed', 'Reserved', 'Dispatched', 'Completed', 'Cancelled'];

    public const PRIORITIES = ['Low', 'Normal', 'High', 'Urgent'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (CustomerOrder $order) {
            if (empty($order->order_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $order->order_number = 'ORD-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($order->priority)) {
                $order->priority = 'Normal';
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(StockReservation::class, 'reservation_id');
    }

    public function plannedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'planned_by_id');
    }

    public function dispatchOrders(): HasMany
    {
        return $this->hasMany(DispatchOrder::class);
    }

    /**
     * Reserves real stock against the best available Stockpile holding this
     * order's material (or a caller-specified one) — reuses Stockpile
     * Management's existing StockReservation machinery rather than
     * duplicating reservation logic.
     */
    public function reserveStock(?int $stockpileId = null): ?StockReservation
    {
        return DB::transaction(function () use ($stockpileId) {
            // The explicit-ID path must apply the same "enough quantity_available"
            // guard the auto-select path already applies, and lockForUpdate()
            // prevents a concurrent reservation against the same stockpile from
            // both passing the availability check before either commits.
            $stockpile = $stockpileId
                ? Stockpile::query()
                    ->where('id', $stockpileId)
                    ->where('quantity_available', '>=', $this->quantity)
                    ->lockForUpdate()
                    ->first()
                : Stockpile::query()
                    ->where('material_id', $this->material_id)
                    ->where('quantity_available', '>=', $this->quantity)
                    ->orderByDesc('quantity_available')
                    ->lockForUpdate()
                    ->first();

            if (! $stockpile) {
                return null;
            }

            $reservation = StockReservation::create([
                'company_id' => $this->company_id,
                'stockpile_id' => $stockpile->id,
                'customer_id' => $this->customer_id,
                'order_number' => $this->order_number,
                'quantity_reserved' => $this->quantity,
                'reservation_date' => now(),
                'status' => 'Reserved',
                'reserved_by_id' => $this->planned_by_id,
            ]);

            $this->update(['reservation_id' => $reservation->id, 'status' => 'Reserved']);

            return $reservation;
        });
    }

    /**
     * Creates the linked DispatchOrder that carries this order through the
     * real Smart Weighbridge lifecycle (check-in, tare, loading, gross
     * weighing, approval, exit, delivery) — DispatchOrder remains the single
     * canonical record, this just seeds it from the customer order.
     */
    public function createDispatchOrder(array $data = []): DispatchOrder
    {
        $order = DispatchOrder::create(array_merge([
            'company_id' => $this->company_id,
            'customer_order_id' => $this->id,
            'customer_id' => $this->customer_id,
            'delivery_address' => $this->customer_site,
            'material_id' => $this->material_id,
            'product_grade' => $this->product_grade,
            'stockpile_id' => $this->reservation?->stockpile_id,
            'reservation_id' => $this->reservation_id,
            'target_net_weight' => $this->quantity,
            'expected_quantity' => $this->quantity,
            'priority' => $this->priority,
            'status' => 'Draft',
        ], $data));

        $this->update(['status' => 'Dispatched']);

        return $order;
    }
}
