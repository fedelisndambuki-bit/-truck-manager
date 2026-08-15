<?php

namespace App\Observers;

use App\Models\EquipmentCostEntry;
use App\Models\TyreRepair;

class TyreRepairObserver
{
    public function created(TyreRepair $tyreRepair): void
    {
        $this->postEquipmentCost($tyreRepair);
    }

    protected function postEquipmentCost(TyreRepair $tyreRepair): void
    {
        $tyre = $tyreRepair->tyre;

        if (! $tyre || ! $tyre->equipment_id || (float) $tyreRepair->cost <= 0) {
            return;
        }

        EquipmentCostEntry::create([
            'company_id' => $tyreRepair->company_id,
            'equipment_id' => $tyre->equipment_id,
            'cost_category' => 'Tyre',
            'amount' => $tyreRepair->cost,
            'incurred_on' => $tyreRepair->repair_date ?: now()->toDateString(),
            'source_type' => TyreRepair::class,
            'source_id' => $tyreRepair->id,
            'description' => "Tyre repair #{$tyreRepair->id} ({$tyreRepair->repair_type}) on tyre {$tyre->serial_number}",
        ]);
    }
}
