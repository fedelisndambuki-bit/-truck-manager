<?php

namespace App\Filament\Resources\WalletFundingResource\Pages;

use App\Filament\Resources\WalletFundingResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageWalletFundings extends ManageRecords
{
    protected static string $resource = WalletFundingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Fund Wallet action is defined in the table headerActions
        ];
    }

    public function mount(): void
    {
        parent::mount();

        // Surface flash messages from wallet.funding.callback redirect
        if (session('success')) {
            Notification::make()
                ->title(session('success'))
                ->success()
                ->persistent()
                ->send();
        }

        if (session('warning')) {
            Notification::make()
                ->title(session('warning'))
                ->warning()
                ->persistent()
                ->send();
        }

        if (session('error')) {
            Notification::make()
                ->title(session('error'))
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
