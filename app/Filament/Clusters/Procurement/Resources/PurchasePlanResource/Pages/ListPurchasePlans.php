<?php

namespace App\Filament\Clusters\Procurement\Resources\PurchasePlanResource\Pages;

use App\Filament\Clusters\Procurement\Resources\PurchasePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchasePlans extends ListRecords
{
    protected static string $resource = PurchasePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
