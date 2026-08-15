<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\ChangeOrderResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\ChangeOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChangeOrders extends ListRecords
{
    protected static string $resource = ChangeOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Clusters\ProjectFinance\Widgets\ChangeOrdersChart::class,
        ];
    }
}
