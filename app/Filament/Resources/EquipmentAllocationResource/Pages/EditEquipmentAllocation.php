<?php

namespace App\Filament\Resources\EquipmentAllocationResource\Pages;

use App\Filament\Resources\EquipmentAllocationResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditEquipmentAllocation extends EditRecord
{
    protected static string $resource = EquipmentAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->approval_status === 'Approved' && ! $this->record->approved_at) {
            $this->record->update([
                'approved_by_id' => Auth::id(),
                'approved_at' => now(),
            ]);
            $this->record->equipment?->update(['status' => 'In Use']);

            Notification::make()->title('Allocation approved')->success()->send();
        }
    }
}
