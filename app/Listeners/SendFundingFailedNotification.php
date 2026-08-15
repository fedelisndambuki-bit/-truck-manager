<?php

namespace App\Listeners;

use App\Events\FundingFailed;
use Filament\Notifications\Notification;

class SendFundingFailedNotification
{
    public function handle(FundingFailed $event): void
    {
        $funding = $event->funding;
        $user = $funding->fundedBy;

        if ($user) {
            Notification::make()
                ->title('Wallet Funding Failed')
                ->danger()
                ->body("Your attempt to fund the wallet with KES " . number_format($funding->amount / 100, 2) . " has failed. " . ($event->error ?? ''))
                ->sendToDatabase($user);
        }
    }
}
