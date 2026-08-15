<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContracts extends ListRecords
{
    protected static string $resource = ContractResource::class;

    protected static string $view = 'filament.resources.contract-resource.pages.list-contracts';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Contract'),
        ];
    }
}
