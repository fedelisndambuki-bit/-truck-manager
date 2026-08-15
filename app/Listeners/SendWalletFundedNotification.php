<?php

namespace App\Listeners;

use App\Events\WalletFunded;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class SendWalletFundedNotification
{
    public function handle(WalletFunded $event): void
    {
        $funding = $event->funding;
        $user = $funding->fundedBy;

        if ($user) {
            Notification::make()
                ->title('Wallet Funded Successfully')
                ->success()
                ->body("Your wallet has been credited with KES " . number_format($funding->amount / 100, 2))
                ->actions([
                    Action::make('view')
                        ->button()
                        ->url(route('filament.admin.resources.wallet-fundings.index')),
                ])
                ->sendToDatabase($user);
        }
    }
}
