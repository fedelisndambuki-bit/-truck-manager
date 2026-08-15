<?php

namespace App\Filament\Resources\SafetyIncidentResource\Pages;

use App\Filament\Resources\SafetyIncidentResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditSafetyIncident extends EditRecord
{
    protected static string $resource = SafetyIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('startInvestigation')
                ->label('Start Investigation')
                ->icon('heroicon-o-magnifying-glass')
                ->color('warning')
                ->visible(fn () => in_array($this->record->status, ['Draft', 'Reported']))
                ->action(fn () => $this->record->update([
                    'status' => 'Under Investigation',
                    'investigation_status' => 'In Progress',
                ]))
                ->after(fn () => $this->refreshFormData(['status', 'investigation_status'])),

            Actions\Action::make('moveToCorrectiveAction')
                ->label('Move to Corrective Action')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('info')
                ->visible(fn () => $this->record->status === 'Under Investigation')
                ->action(fn () => $this->record->update([
                    'status' => 'Corrective Action',
                    'investigation_status' => 'Completed',
                ]))
                ->after(fn () => $this->refreshFormData(['status', 'investigation_status'])),

            Actions\Action::make('approveAndClose')
                ->label('Approve & Close')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['Under Investigation', 'Corrective Action']))
                ->form([
                    Forms\Components\Select::make('approved_by_id')->label('Approved By')->relationship('approvedBy', 'full_name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('closed_by_id')->label('Closed By')->relationship('closedBy', 'full_name')->searchable()->preload()->required(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'Closed',
                        'approved_by_id' => $data['approved_by_id'],
                        'approved_at' => now(),
                        'closed_by_id' => $data['closed_by_id'],
                        'closed_at' => now(),
                    ]);
                })
                ->after(fn () => $this->refreshFormData(['status', 'approved_by_id', 'approved_at', 'closed_by_id', 'closed_at'])),

            Actions\DeleteAction::make(),
        ];
    }
}
