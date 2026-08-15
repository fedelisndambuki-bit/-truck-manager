<?php

namespace App\Filament\Resources\ScopeOfWorkResource\Pages;

use App\Filament\Resources\ScopeOfWorkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScopeOfWorks extends ListRecords
{
    protected static string $resource = ScopeOfWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Define Scope'),
        ];
    }
}
