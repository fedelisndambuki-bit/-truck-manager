<?php

namespace App\Filament\Clusters\Procurement\Resources\FrameworkAgreementResource\Pages;

use App\Filament\Clusters\Procurement\Resources\FrameworkAgreementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFrameworkAgreements extends ListRecords
{
    protected static string $resource = FrameworkAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
