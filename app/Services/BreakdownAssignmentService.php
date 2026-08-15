<?php

namespace App\Services;

use App\Models\Breakdown;
use App\Models\MaintenanceWorkOrder;
use App\Models\Staff;

/**
 * Pragmatic auto-assignment for breakdown reports — picks the least-busy
 * technician (fewest open work orders) from the pool of staff who have
 * previously worked a maintenance work order in this company, falling back
 * to any staff member if no technician has been assigned yet. Not a
 * scheduling optimizer; a single query + in-memory sort.
 */
class BreakdownAssignmentService
{
    public function autoAssignTechnician(Breakdown $breakdown): ?Staff
    {
        $technicianIds = MaintenanceWorkOrder::query()
            ->when($breakdown->company_id, fn ($q) => $q->where('company_id', $breakdown->company_id))
            ->whereNotNull('technician_id')
            ->distinct()
            ->pluck('technician_id');

        $technicians = $technicianIds->isNotEmpty()
            ? Staff::query()->whereIn('id', $technicianIds)->get()
            : Staff::query()->when($breakdown->company_id, fn ($q) => $q->where('company_id', $breakdown->company_id))->get();

        if ($technicians->isEmpty()) {
            return null;
        }

        $openCounts = MaintenanceWorkOrder::query()
            ->when($breakdown->company_id, fn ($q) => $q->where('company_id', $breakdown->company_id))
            ->whereNotIn('status', ['Closed'])
            ->whereIn('technician_id', $technicians->pluck('id'))
            ->selectRaw('technician_id, count(*) as open_count')
            ->groupBy('technician_id')
            ->pluck('open_count', 'technician_id');

        return $technicians->sortBy(fn (Staff $tech) => $openCounts->get($tech->id, 0))->first();
    }
}
