<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseRequest extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    protected $casts = [
        'required_delivery_date' => 'date',
        'expected_cost' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (PurchaseRequest $request) {
            if (empty($request->pr_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $request->pr_number = 'PR-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requester_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approver_id');
    }

    public function materialRequest(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function costCentre(): BelongsTo
    {
        return $this->belongsTo(CostCentre::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ProcurementDocument::class, 'documentable');
    }

    public function rfqs(): HasMany
    {
        return $this->hasMany(RequestForQuotation::class);
    }

    public function totalEstimatedCost(): float
    {
        // Summed in PHP rather than via a SQL alias: aliasing this as "total"
        // would collide with PurchaseRequestItem::getTotalAttribute() and
        // silently return 0 (the accessor shadows the raw aggregate).
        $itemsTotal = (float) $this->items()->get()->sum(fn (PurchaseRequestItem $item) => (float) $item->quantity * (float) $item->estimated_price);

        return $itemsTotal > 0 ? $itemsTotal : (float) $this->expected_cost;
    }

    /**
     * Create one PurchaseRequest per active material below its reorder_level
     * that doesn't already have an open (Pending/Approved) auto-generated PR.
     */
    public static function generateFromLowStock(int $companyId): int
    {
        $lowStockMaterials = Material::query()
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->where('reorder_level', '>', 0)
            ->get();

        $created = 0;

        foreach ($lowStockMaterials as $material) {
            $hasOpenPr = static::query()
                ->where('material_id', $material->id)
                ->where('source', 'Auto')
                ->whereIn('status', ['Pending', 'Approved'])
                ->exists();

            if ($hasOpenPr) {
                continue;
            }

            $quantityNeeded = max(
                (float) ($material->max_stock ?? $material->reorder_level * 2) - (float) $material->quantity_on_hand,
                (float) $material->reorder_level
            );

            static::create([
                'company_id' => $companyId,
                'material_id' => $material->id,
                'quantity' => $quantityNeeded,
                'estimated_cost' => $quantityNeeded * (float) ($material->standard_cost ?? $material->unit_cost ?? 0),
                'priority' => 'High',
                'source' => 'Auto',
                'status' => 'Pending',
            ]);

            $created++;
        }

        return $created;
    }
}
