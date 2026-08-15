<?php

namespace App\Services;

use App\Models\MaintenanceWorkOrder;
use App\Models\PreventiveMaintenancePlan;
use Illuminate\Support\Collection;

/**
 * Evaluates active PM plans and generates a MaintenanceWorkOrder once a
 * plan's due condition is met — a straightforward per-plan if/else, not a
 * generic rule engine.
 */
class PreventiveMaintenanceSchedulingService
{
    public function generateDueWorkOrders(?int $companyId = null): Collection
    {
        $created = collect();

        PreventiveMaintenancePlan::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->with('equipment')
            ->get()
            ->each(function (PreventiveMaintenancePlan $plan) use (&$created) {
                if ($plan->hasOpenWorkOrder() || ! $this->isDue($plan)) {
                    return;
                }

                $created->push($this->generateWorkOrder($plan));
            });

        return $created;
    }

    protected function isDue(PreventiveMaintenancePlan $plan): bool
    {
        $equipment = $plan->equipment;

        if (! $equipment) {
            return false;
        }

        return match ($plan->trigger_type) {
            'Calendar' => $plan->next_due_value !== null
                ? now()->timestamp >= (float) $plan->next_due_value
                : true,
            'EngineHours' => (float) $equipment->engine_hours >= (float) ($plan->next_due_value ?? $plan->interval_value),
            'Km' => false, // Equipment has no odometer field yet — Km-based plans are configured but not auto-evaluated until one exists.
            'FuelUsage' => false, // Requires aggregated fuel usage since last trigger — deferred until Phase 4 fuel integration lands.
            'ProductionCycles' => false, // Requires cycle counts from EquipmentUtilizationLog — evaluated manually until then.
            default => false,
        };
    }

    protected function generateWorkOrder(PreventiveMaintenancePlan $plan): MaintenanceWorkOrder
    {
        $order = MaintenanceWorkOrder::create([
            'company_id' => $plan->company_id,
            'equipment_id' => $plan->equipment_id,
            'preventive_maintenance_plan_id' => $plan->id,
            'work_order_type' => 'Preventive',
            'priority' => 'Medium',
            'status' => 'Created',
            'description' => "Preventive maintenance — {$plan->name}",
            'estimated_hours' => $plan->estimated_duration_hours,
            'required_parts' => $plan->required_parts,
        ]);

        $advance = match ($plan->trigger_type) {
            'Calendar' => now()->timestamp,
            'EngineHours' => (float) $plan->equipment->engine_hours,
            default => $plan->next_due_value,
        };

        $plan->update([
            'last_triggered_at_value' => $advance,
            'next_due_value' => $plan->trigger_type === 'Calendar'
                ? now()->addDays((int) $plan->interval_value)->timestamp
                : ((float) $advance + (float) $plan->interval_value),
        ]);

        return $order;
    }
}
