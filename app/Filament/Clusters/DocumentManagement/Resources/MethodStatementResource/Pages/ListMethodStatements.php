<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\MethodStatementResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\MethodStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMethodStatements extends ListRecords
{
    protected static string $resource = MethodStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
