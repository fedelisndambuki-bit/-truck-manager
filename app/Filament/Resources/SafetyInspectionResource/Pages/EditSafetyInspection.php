<?php

namespace App\Filament\Resources\SafetyInspectionResource\Pages;

use App\Filament\Resources\SafetyInspectionResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditSafetyInspection extends EditRecord
{
    protected static string $resource = SafetyInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('submit')
                ->label('Submit')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'Draft')
                ->action(fn () => $this->record->update(['status' => 'Submitted']))
                ->after(fn () => $this->refreshFormData(['status'])),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->status === 'Submitted')
                ->form([
                    Forms\Components\Select::make('approved_by_id')->label('Approved By')->relationship('approvedBy', 'full_name')->searchable()->preload()->required(),
                ])
                ->action(fn (array $data) => $this->record->update([
                    'status' => 'Approved',
                    'approved_by_id' => $data['approved_by_id'],
                    'approved_at' => now(),
                ]))
                ->after(fn () => $this->refreshFormData(['status', 'approved_by_id', 'approved_at'])),

            Actions\DeleteAction::make(),
        ];
    }
}
