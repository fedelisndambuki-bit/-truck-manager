<?php

namespace App\Filament\Resources\PpeIssuanceResource\Pages;

use App\Filament\Resources\PpeIssuanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPpeIssuances extends ListRecords
{
    protected static string $resource = PpeIssuanceResource::class;

    protected static string $view = 'filament.resources.ppe-issuance-resource.pages.list-ppe-issuances';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Issue PPE'),
        ];
    }
}
