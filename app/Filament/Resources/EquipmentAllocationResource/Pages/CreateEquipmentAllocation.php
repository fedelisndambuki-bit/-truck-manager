<?php

namespace App\Filament\Resources\EquipmentAllocationResource\Pages;

use App\Filament\Resources\EquipmentAllocationResource;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Illuminate\Support\Facades\Auth;

class CreateEquipmentAllocation extends CreateRecord
{
    use HasWizard;

    protected static string $resource = EquipmentAllocationResource::class;

    protected function hasSkippableSteps(): bool
    {
        return true;
    }

    public function getSteps(): array
    {
        return [
            Step::make('Select Equipment')
                ->icon('heroicon-o-truck')
                ->schema(EquipmentAllocationResource::selectEquipmentSchema()),
            Step::make('Assign Project')
                ->icon('heroicon-o-building-office-2')
                ->schema(EquipmentAllocationResource::assignProjectSchema())
                ->columns(2),
            Step::make('Assign Operator')
                ->icon('heroicon-o-user')
                ->schema(EquipmentAllocationResource::assignOperatorSchema())
                ->columns(2),
            Step::make('Operational Info')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema(EquipmentAllocationResource::operationalInfoSchema())
                ->columns(2),
            Step::make('Approval')
                ->icon('heroicon-o-check-badge')
                ->schema(EquipmentAllocationResource::approvalSchema()),
        ];
    }

    protected function afterCreate(): void
    {
        if ($this->record->approval_status === 'Approved') {
            $this->record->update([
                'approved_by_id' => Auth::id(),
                'approved_at' => now(),
            ]);
            $this->record->equipment?->update(['status' => 'In Use']);

            Notification::make()->title('Allocation approved')->success()->send();
        }
    }
}
