<?php

namespace App\Filament\SuperAdmin\Resources\SubscriptionPaymentResource\Pages;

use App\Filament\SuperAdmin\Resources\SubscriptionPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionPayments extends ListRecords
{
    protected static string $resource = SubscriptionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
