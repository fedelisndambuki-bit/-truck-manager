<?php

namespace App\Filament\Clusters\Finance\Resources\ChartOfAccountResource\Pages;

use App\Filament\Clusters\Finance\Resources\ChartOfAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChartOfAccount extends EditRecord
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->visible(fn () => ! $this->record->is_system)];
    }
}
