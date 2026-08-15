<?php

namespace App\Filament\Resources\LabourerResource\Pages;

use App\Filament\Resources\LabourerResource;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Illuminate\Support\Facades\Auth;

class CreateLabourer extends CreateRecord
{
    use HasWizard;

    protected static string $resource = LabourerResource::class;

    protected function hasSkippableSteps(): bool
    {
        return true;
    }

    public function getSteps(): array
    {
        return [
            Step::make('Select Employee')
                ->icon('heroicon-o-user')
                ->schema(LabourerResource::selectEmployeeSchema())
                ->columns(2),
            Step::make('Assignment')
                ->icon('heroicon-o-building-office-2')
                ->schema(LabourerResource::assignmentSchema())
                ->columns(2),
            Step::make('Schedule & Rate')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema(LabourerResource::scheduleSchema())
                ->columns(2),
            Step::make('Approval')
                ->icon('heroicon-o-check-badge')
                ->schema(LabourerResource::approvalSchema()),
        ];
    }

    protected function afterCreate(): void
    {
        if ($this->record->approval_status === 'Approved') {
            $this->record->update([
                'approved_by_id' => Auth::id(),
                'approved_at' => now(),
            ]);

            Notification::make()->title('Assignment approved')->success()->send();
        }
    }
}
