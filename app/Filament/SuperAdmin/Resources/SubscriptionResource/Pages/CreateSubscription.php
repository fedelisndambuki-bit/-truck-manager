<?php

namespace App\Filament\SuperAdmin\Resources\SubscriptionResource\Pages;

use App\Filament\SuperAdmin\Resources\SubscriptionResource;
use App\Services\SubscriptionService;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    /**
     * Without this, creating a Subscription here never touched the
     * company's own plan_id/subscription_id/status — the tenant's actual
     * access was unaffected regardless of what was created.
     */
    protected function afterCreate(): void
    {
        app(SubscriptionService::class)->syncCompanyFromSubscription($this->record);
    }
}
