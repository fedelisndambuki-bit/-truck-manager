<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\RetentionResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\RetentionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRetentionEntries extends ListRecords
{
    protected static string $resource = RetentionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Clusters\ProjectFinance\Widgets\RetentionChart::class,
        ];
    }
}
