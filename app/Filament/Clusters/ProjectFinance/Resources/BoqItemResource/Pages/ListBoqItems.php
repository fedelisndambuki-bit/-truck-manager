<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\BoqItemResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\BoqItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBoqItems extends ListRecords
{
    protected static string $resource = BoqItemResource::class;

    protected static string $view = 'filament.clusters.project-finance.resources.boq-item-resource.pages.list-boq-items';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
