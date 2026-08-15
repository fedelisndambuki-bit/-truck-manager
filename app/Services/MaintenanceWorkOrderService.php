<?php

namespace App\Services;

use App\Models\Maintenance;
use App\Models\MaintenanceWorkOrder;
use App\Models\Part;
use App\Models\PartUsage;

/**
 * The Created -> Assigned -> In Progress -> Waiting Parts -> Completed ->
 * Closed work order lifecycle. close() writes the Service History
 * (Maintenance) row and consumes reserved parts; posting the cost entry
 * and Finance ledger happens in MaintenanceObserver (fired when the
 * Maintenance row's cost is set), so every equipment-linked Maintenance
 * record gets cost-posted the same way regardless of whether it came from
 * a work order or the older "Schedule Maintenance" action.
 */
class MaintenanceWorkOrderService
{
    public function assign(MaintenanceWorkOrder $order, int $technicianId, ?int $workshopId = null, ?int $workshopBayId = null): MaintenanceWorkOrder
    {
        $order->update([
            'technician_id' => $technicianId,
            'workshop_id' => $workshopId ?? $order->workshop_id,
            'workshop_bay_id' => $workshopBayId ?? $order->workshop_bay_id,
            'status' => 'Assigned',
            'assigned_at' => now(),
        ]);

        return $order->refresh();
    }

    public function start(MaintenanceWorkOrder $order): MaintenanceWorkOrder
    {
        $order->update(['status' => 'In Progress', 'started_at' => $order->started_at ?? now()]);

        return $order->refresh();
    }

    public function markWaitingParts(MaintenanceWorkOrder $order): MaintenanceWorkOrder
    {
        $order->update(['status' => 'Waiting Parts']);

        return $order->refresh();
    }

    public function complete(MaintenanceWorkOrder $order, ?float $actualHours = null): MaintenanceWorkOrder
    {
        $order->update([
            'status' => 'Completed',
            'completed_at' => now(),
            'actual_hours' => $actualHours ?? $order->actual_hours,
        ]);

        return $order->refresh();
    }

    /**
     * Close the work order: consume reserved parts, post costs, create the
     * Service History record, and post to the Finance ledger.
     */
    public function close(MaintenanceWorkOrder $order, int $closedById): MaintenanceWorkOrder
    {
        // Service History row must exist before parts can be consumed
        // against it — part_usages.maintenance_id is a required FK.
        $maintenance = Maintenance::updateOrCreate(
            ['maintenance_work_order_id' => $order->id],
            [
                'company_id' => $order->company_id,
                'equipment_id' => $order->equipment_id,
                'technician_id' => $order->technician_id,
                'maintenance_type' => $order->work_order_type,
                'description' => $order->description,
                'maintenance_date' => now(),
                'status' => 'Completed',
                'downtime_hours' => $order->actual_hours,
            ]
        );

        $partsCost = $this->consumeParts($order, $maintenance);

        $laborCost = (float) ($order->labor_cost ?? 0);
        $outsideCost = (float) ($order->outside_services_cost ?? 0);
        $totalCost = $laborCost + $outsideCost + $partsCost;

        $order->update([
            'status' => 'Closed',
            'closed_at' => now(),
            'closed_by_id' => $closedById,
            'parts_cost' => $partsCost,
            'total_cost' => $totalCost,
        ]);

        // Triggers MaintenanceObserver::updated() -> cost posting (EquipmentCostEntry + Finance ledger).
        $maintenance->update(['cost' => $totalCost]);

        return $order->refresh();
    }

    protected function consumeParts(MaintenanceWorkOrder $order, Maintenance $maintenance): float
    {
        $required = $order->required_parts ?? [];
        $total = 0.0;

        foreach ($required as $line) {
            $partId = $line['part_id'] ?? null;
            $quantity = (int) ($line['quantity'] ?? 0);

            if (! $partId || $quantity <= 0) {
                continue;
            }

            $part = Part::find($partId);

            if (! $part) {
                continue;
            }

            $lineCost = (float) $part->unit_price * $quantity;
            $total += $lineCost;

            // PartUsageObserver::created() already decrements Part.stock_quantity
            // — do not also decrement here, or stock is deducted twice.
            PartUsage::create([
                'company_id' => $order->company_id,
                'maintenance_id' => $maintenance->id,
                'part_id' => $part->id,
                'quantity' => $quantity,
                'unit_price' => $part->unit_price,
                'total_price' => $lineCost,
            ]);
        }

        return $total;
    }
}
