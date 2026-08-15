<?php

namespace App\Filament\SuperAdmin\Resources\SubscriptionResource\Pages;

use App\Filament\SuperAdmin\Resources\SubscriptionResource;
use App\Services\SubscriptionService;
use Filament\Resources\Pages\EditRecord;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    /**
     * Without this, changing a Subscription's plan/status here never
     * touched the company's own plan_id/subscription_id/status — an admin
     * could "activate" or "expire" a subscription with no real effect on
     * the tenant's actual access.
     */
    protected function afterSave(): void
    {
        app(SubscriptionService::class)->syncCompanyFromSubscription($this->record);
    }
}
