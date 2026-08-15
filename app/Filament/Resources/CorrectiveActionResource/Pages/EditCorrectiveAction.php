<?php

namespace App\Filament\Resources\CorrectiveActionResource\Pages;

use App\Filament\Resources\CorrectiveActionResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditCorrectiveAction extends EditRecord
{
    protected static string $resource = CorrectiveActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('verify')
                ->label('Verify')
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->visible(fn () => $this->record->status === 'Completed')
                ->form([
                    Forms\Components\Select::make('verified_by_id')->label('Verified By')->relationship('verifiedBy', 'full_name')->searchable()->preload()->required(),
                ])
                ->action(fn (array $data) => $this->record->update([
                    'status' => 'Verified',
                    'verified_by_id' => $data['verified_by_id'],
                    'verified_at' => now(),
                ]))
                ->after(fn () => $this->refreshFormData(['status', 'verified_by_id', 'verified_at'])),

            Actions\Action::make('close')
                ->label('Approve & Close')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->status === 'Verified')
                ->form([
                    Forms\Components\Select::make('closure_approved_by_id')->label('Closure Approved By')->relationship('closureApprovedBy', 'full_name')->searchable()->preload()->required(),
                ])
                ->action(fn (array $data) => $this->record->update([
                    'status' => 'Closed',
                    'progress_percent' => 100,
                    'closure_approved_by_id' => $data['closure_approved_by_id'],
                    'closure_approved_at' => now(),
                ]))
                ->after(fn () => $this->refreshFormData(['status', 'progress_percent', 'closure_approved_by_id', 'closure_approved_at'])),

            Actions\DeleteAction::make(),
        ];
    }
}
