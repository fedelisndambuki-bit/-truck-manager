<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\LoadingBayResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\LoadingBayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoadingBays extends ListRecords
{
    protected static string $resource = LoadingBayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
