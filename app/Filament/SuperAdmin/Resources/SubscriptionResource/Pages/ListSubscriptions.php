<?php

namespace App\Filament\SuperAdmin\Resources\SubscriptionResource\Pages;

use App\Filament\SuperAdmin\Resources\SubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected static string $view = 'filament.super-admin.resources.subscription-resource.pages.list-subscriptions';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
