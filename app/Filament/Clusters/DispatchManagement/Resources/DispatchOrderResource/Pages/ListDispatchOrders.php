<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\DispatchOrderResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\DispatchOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDispatchOrders extends ListRecords
{
    protected static string $resource = DispatchOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
