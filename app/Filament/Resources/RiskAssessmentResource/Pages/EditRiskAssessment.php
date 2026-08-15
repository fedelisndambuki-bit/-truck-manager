<?php

namespace App\Filament\Resources\RiskAssessmentResource\Pages;

use App\Filament\Resources\RiskAssessmentResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditRiskAssessment extends EditRecord
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('submitForApproval')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'Draft')
                ->action(fn () => $this->record->update(['status' => 'Pending Approval']))
                ->after(fn () => $this->refreshFormData(['status'])),

            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->status === 'Pending Approval')
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
