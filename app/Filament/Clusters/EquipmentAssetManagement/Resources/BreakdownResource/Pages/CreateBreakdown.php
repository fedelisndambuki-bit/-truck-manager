<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\BreakdownResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\BreakdownResource;
use App\Models\MaintenanceWorkOrder;
use App\Services\BreakdownAssignmentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBreakdown extends CreateRecord
{
    protected static string $resource = BreakdownResource::class;

    /**
     * Every new breakdown report auto-spawns its work order and, where a
     * technician is available, auto-assigns it — matching the "assign
     * technicians automatically" requirement.
     */
    protected function afterCreate(): void
    {
        $breakdown = $this->record;

        $workOrder = MaintenanceWorkOrder::create([
            'company_id' => $breakdown->company_id,
            'equipment_id' => $breakdown->equipment_id,
            'work_order_type' => 'Breakdown',
            'priority' => match ($breakdown->severity) {
                'Critical' => 'Critical', 'High' => 'High', 'Low' => 'Low', default => 'Medium',
            },
            'status' => 'Created',
            'description' => "Breakdown {$breakdown->breakdown_number}: {$breakdown->description}",
        ]);

        $technician = app(BreakdownAssignmentService::class)->autoAssignTechnician($breakdown);

        if ($technician) {
            $workOrder->update(['technician_id' => $technician->id, 'status' => 'Assigned', 'assigned_at' => now()]);
            $breakdown->update(['status' => 'Assigned']);

            Notification::make()
                ->title("Work order {$workOrder->work_order_number} auto-assigned to {$technician->full_name}")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title("Work order {$workOrder->work_order_number} created — no technician available for auto-assignment")
                ->warning()
                ->send();
        }

        $breakdown->update(['maintenance_work_order_id' => $workOrder->id]);
    }
}
