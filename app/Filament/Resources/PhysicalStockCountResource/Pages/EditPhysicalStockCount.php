<?php

namespace App\Filament\Resources\PhysicalStockCountResource\Pages;

use App\Filament\Resources\PhysicalStockCountResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPhysicalStockCount extends EditRecord
{
    protected static string $resource = PhysicalStockCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('completeCount')
                ->label('Complete Count')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('This sets warehouse stock to the counted values and creates audit adjustment records for any variance found.')
                ->action(function () {
                    $this->record->complete();
                    Notification::make()->title('Stock count completed and reconciled')->success()->send();
                })
                ->visible(fn () => $this->record->status !== 'Completed'
                    && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('materials.count'))),
            Actions\DeleteAction::make(),
        ];
    }
}
