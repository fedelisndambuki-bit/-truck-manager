<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\CustomerOrderResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\CustomerOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerOrders extends ListRecords
{
    protected static string $resource = CustomerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
