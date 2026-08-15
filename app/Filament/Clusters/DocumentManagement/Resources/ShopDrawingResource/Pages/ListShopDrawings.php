<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\ShopDrawingResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\ShopDrawingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShopDrawings extends ListRecords
{
    protected static string $resource = ShopDrawingResource::class;

    protected static string $view = 'filament.clusters.document-management.resources.shop-drawing-resource.pages.list-shop-drawings';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
