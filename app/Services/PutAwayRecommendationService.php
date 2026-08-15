<?php

namespace App\Services;

use App\Models\BinLocation;
use App\Models\Material;
use App\Models\Warehouse;

/**
 * Pragmatic bin-recommendation heuristic for Put-Away tasks. Deliberately
 * simple: MaterialCategory has no hazard/zone-affinity data today, so this
 * doesn't attempt hazard-aware or item-type-aware placement — only
 * consolidation (fill a bin that already has this material) and generic
 * zone-type/capacity fitting.
 */
class PutAwayRecommendationService
{
    public function recommend(Material $material, Warehouse $warehouse, float $quantity): ?BinLocation
    {
        return $this->consolidationCandidate($material, $warehouse, $quantity)
            ?? $this->emptyStorageCandidate($warehouse, $quantity)
            ?? $this->anyEmptyCandidate($warehouse, $quantity);
    }

    /**
     * A bin in this warehouse already holding the material, with room for
     * this quantity, preferring the one with the least remaining capacity
     * (fill it up before opening a new bin).
     */
    protected function consolidationCandidate(Material $material, Warehouse $warehouse, float $quantity): ?BinLocation
    {
        $candidates = BinLocation::where('status', 'Active')
            ->where('warehouse_id', $warehouse->id)
            ->whereHas('binStock', fn ($q) => $q->where('material_id', $material->id)->where('quantity', '>', 0))
            ->get();

        return $candidates
            ->filter(fn (BinLocation $bin) => $this->remainingCapacity($bin) === null || $this->remainingCapacity($bin) >= $quantity)
            ->sortBy(fn (BinLocation $bin) => $this->remainingCapacity($bin) ?? PHP_FLOAT_MAX)
            ->first();
    }

    /**
     * An empty bin in a Storage-type zone with enough capacity.
     */
    protected function emptyStorageCandidate(Warehouse $warehouse, float $quantity): ?BinLocation
    {
        return BinLocation::where('status', 'Active')
            ->where('warehouse_id', $warehouse->id)
            ->whereHas('zone', fn ($q) => $q->where('type', 'Storage'))
            ->whereDoesntHave('binStock', fn ($q) => $q->where('quantity', '>', 0))
            ->get()
            ->first(fn (BinLocation $bin) => $this->remainingCapacity($bin) === null || $this->remainingCapacity($bin) >= $quantity);
    }

    /**
     * Any empty, active bin in the warehouse regardless of zone.
     */
    protected function anyEmptyCandidate(Warehouse $warehouse, float $quantity): ?BinLocation
    {
        return BinLocation::where('status', 'Active')
            ->where('warehouse_id', $warehouse->id)
            ->whereDoesntHave('binStock', fn ($q) => $q->where('quantity', '>', 0))
            ->get()
            ->first(fn (BinLocation $bin) => $this->remainingCapacity($bin) === null || $this->remainingCapacity($bin) >= $quantity);
    }

    protected function remainingCapacity(BinLocation $bin): ?float
    {
        if ($bin->capacity === null) {
            return null;
        }

        return (float) $bin->capacity - $bin->total_stock;
    }
}
