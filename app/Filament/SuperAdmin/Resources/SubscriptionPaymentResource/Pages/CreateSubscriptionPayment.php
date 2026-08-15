<?php

namespace App\Filament\SuperAdmin\Resources\SubscriptionPaymentResource\Pages;

use App\Filament\SuperAdmin\Resources\SubscriptionPaymentResource;
use App\Services\SubscriptionService;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscriptionPayment extends CreateRecord
{
    protected static string $resource = SubscriptionPaymentResource::class;

    /**
     * Without this, recording a payment as "Paid" here was pure
     * bookkeeping — it never activated/extended the linked subscription
     * or the company's own access.
     */
    protected function afterCreate(): void
    {
        app(SubscriptionService::class)->confirmManualPayment($this->record);
    }
}
