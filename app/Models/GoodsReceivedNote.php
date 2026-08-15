<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceivedNote extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'quality_approved' => 'boolean',
        'checklist' => 'array',
        'photos' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (GoodsReceivedNote $grn) {
            if (empty($grn->grn_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $grn->grn_number = 'GRN-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function materialDelivery(): BelongsTo
    {
        return $this->belongsTo(MaterialDelivery::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'grn_id');
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'inspected_by_id');
    }

    /**
     * Credit the received warehouse's StockLevel with the accepted quantity
     * and recalculate the material's cached quantity_on_hand rollup. This is
     * the single place goods receipt actually moves stock.
     *
     * @throws \RuntimeException if the delivery has no warehouse assigned.
     */
    public function approve(): void
    {
        $delivery = $this->materialDelivery;

        if (! $delivery->warehouse_id) {
            throw new \RuntimeException('Cannot approve this GRN: the delivery has no warehouse assigned.');
        }

        $stockLevel = StockLevel::firstOrCreate(
            [
                'material_id' => $delivery->material_id,
                'warehouse_id' => $delivery->warehouse_id,
                'batch_number' => '',
            ],
            [
                'company_id' => $this->company_id,
                'project_id' => $delivery->project_id,
                'site_id' => $delivery->site_id,
                'supplier_id' => $delivery->supplier_id,
            ]
        );

        $stockLevel->increment('quantity_on_hand', $this->accepted_quantity);

        if ($this->damaged_quantity > 0) {
            $stockLevel->increment('damaged_quantity', $this->damaged_quantity);
        }

        $delivery->material->recalculateStock();

        if ($this->accepted_quantity > 0) {
            InventoryMovement::record([
                'company_id' => $this->company_id,
                'movement_type' => 'Receipt',
                'material_id' => $delivery->material_id,
                'warehouse_id' => $delivery->warehouse_id,
                'quantity' => $this->accepted_quantity,
                'operator_id' => $this->inspected_by_id,
                'description' => "GRN {$this->grn_number} accepted",
            ], $this);

            $recommendedBin = app(\App\Services\PutAwayRecommendationService::class)->recommend(
                $delivery->material,
                $delivery->warehouse,
                (float) $this->accepted_quantity
            );

            PutAwayTask::create([
                'company_id' => $this->company_id,
                'goods_received_note_id' => $this->id,
                'material_id' => $delivery->material_id,
                'warehouse_id' => $delivery->warehouse_id,
                'quantity' => $this->accepted_quantity,
                'recommended_bin_id' => $recommendedBin?->id,
                'status' => 'Pending',
            ]);
        }

        $this->update(['status' => 'Approved']);
    }
}
