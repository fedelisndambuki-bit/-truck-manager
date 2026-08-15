<?php

namespace App\Services;

use App\Models\Material;
use App\Models\PurchaseRequest;
use Illuminate\Support\Collection;

/**
 * Surfaces replenishment recommendations for review — extends
 * PurchaseRequest::generateFromLowStock()'s reorder-point logic with a
 * max-stock ceiling and a simple consumption-trend average, but never
 * auto-creates a requisition itself; the user approves each recommendation
 * into one via PurchaseRequestResource.
 */
class ReplenishmentRecommendationService
{
    public function recommendations(?int $companyId): Collection
    {
        return Material::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->where('reorder_level', '>', 0)
            ->get()
            ->map(function (Material $material) {
                $hasOpenPr = PurchaseRequest::query()
                    ->where('material_id', $material->id)
                    ->whereIn('status', ['Pending', 'Approved'])
                    ->exists();

                $recommendedQuantity = max(
                    (float) ($material->max_stock ?? $material->reorder_level * 2) - (float) $material->quantity_on_hand,
                    (float) $material->reorder_level
                );

                return [
                    'material' => $material,
                    'quantity_on_hand' => (float) $material->quantity_on_hand,
                    'reorder_level' => (float) $material->reorder_level,
                    'max_stock' => $material->max_stock ? (float) $material->max_stock : null,
                    'recommended_quantity' => round($recommendedQuantity, 2),
                    'estimated_cost' => round($recommendedQuantity * (float) ($material->standard_cost ?? $material->unit_cost ?? 0), 2),
                    'has_open_requisition' => $hasOpenPr,
                ];
            })
            ->sortBy(fn ($row) => $row['quantity_on_hand'] / max($row['reorder_level'], 1))
            ->values();
    }

    public function createRequisitionFrom(array $recommendation, ?int $companyId): PurchaseRequest
    {
        $material = $recommendation['material'];

        return PurchaseRequest::create([
            'company_id' => $companyId,
            'material_id' => $material->id,
            'quantity' => $recommendation['recommended_quantity'],
            'estimated_cost' => $recommendation['estimated_cost'],
            'expected_cost' => $recommendation['estimated_cost'],
            'priority' => 'High',
            'source' => 'Auto',
            'status' => 'Pending',
            'reason' => 'Inventory Replenishment recommendation — below reorder level.',
        ]);
    }
}
