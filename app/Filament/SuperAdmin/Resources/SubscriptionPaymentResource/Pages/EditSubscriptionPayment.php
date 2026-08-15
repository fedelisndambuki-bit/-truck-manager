<?php

namespace App\Filament\SuperAdmin\Resources\SubscriptionPaymentResource\Pages;

use App\Filament\SuperAdmin\Resources\SubscriptionPaymentResource;
use App\Services\SubscriptionService;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionPayment extends EditRecord
{
    protected static string $resource = SubscriptionPaymentResource::class;

    /**
     * Without this, marking a payment "Paid" here was pure bookkeeping —
     * it never activated/extended the linked subscription or the
     * company's own access.
     */
    protected function afterSave(): void
    {
        app(SubscriptionService::class)->confirmManualPayment($this->record);
    }
}
