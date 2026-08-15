<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\FuelLog;

class FuelLogObserver
{
    public function created(FuelLog $fuelLog): void
    {
        $this->updateTruckMileage($fuelLog);
        $this->createExpense($fuelLog);
        $this->postEquipmentCost($fuelLog);
    }

    public function updated(FuelLog $fuelLog): void
    {
        $this->updateTruckMileage($fuelLog);
        $this->syncExpense($fuelLog);
    }

    /**
     * Equipment & Asset Management unified cost ledger — only posted once,
     * on creation, since fuel-up records aren't typically re-edited with a
     * different cost the way a maintenance job's total is.
     */
    protected function postEquipmentCost(FuelLog $fuelLog): void
    {
        if (! $fuelLog->equipment_id || (float) $fuelLog->total_cost <= 0) {
            return;
        }

        \App\Models\EquipmentCostEntry::create([
            'company_id' => $fuelLog->company_id,
            'equipment_id' => $fuelLog->equipment_id,
            'cost_category' => 'Fuel',
            'amount' => $fuelLog->total_cost,
            'incurred_on' => $fuelLog->date ?: now()->toDateString(),
            'source_type' => FuelLog::class,
            'source_id' => $fuelLog->id,
            'description' => "Fuel log #{$fuelLog->id}: {$fuelLog->quantity_litres}L at {$fuelLog->fuel_station}",
        ]);
    }

    protected function createExpense(FuelLog $fuelLog): void
    {
        if ($fuelLog->total_cost <= 0) return;

        Expense::create([
            'company_id'    => $fuelLog->company_id ?: 1,
            'truck_id'      => $fuelLog->truck_id,
            'employee_id'   => $fuelLog->employee_id,
            'fuel_log_id'   => $fuelLog->id,
            'category'      => 'Fuel',
            'amount'        => $fuelLog->total_cost,
            'date'          => $fuelLog->date ?: now(),
            'payment_method' => 'Cash',
            'description'   => "Fuel Log #{$fuelLog->id}: {$fuelLog->quantity_litres}L @ {$fuelLog->cost_per_litre} at {$fuelLog->fuel_station}",
        ]);
    }

    protected function syncExpense(FuelLog $fuelLog): void
    {
        if ($fuelLog->total_cost <= 0) return;

        $expense = Expense::where('fuel_log_id', $fuelLog->id)->first();
        if (! $expense) return;

        $expense->update([
            'amount'      => $fuelLog->total_cost,
            'date'        => $fuelLog->date ?: $expense->date,
            'description' => "Fuel Log #{$fuelLog->id}: {$fuelLog->quantity_litres}L @ {$fuelLog->cost_per_litre} at {$fuelLog->fuel_station}",
        ]);
    }

    protected function updateTruckMileage(FuelLog $fuelLog): void
    {
        $truck = $fuelLog->truck;
        if (! $truck) return;

        if ($fuelLog->mileage_at_fueling > ($truck->current_mileage ?? 0)) {
            $truck->current_mileage = $fuelLog->mileage_at_fueling;
            $truck->save();
        }
    }
}
