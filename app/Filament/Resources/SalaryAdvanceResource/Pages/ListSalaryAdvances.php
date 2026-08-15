<?php

namespace App\Filament\Resources\SalaryAdvanceResource\Pages;

use App\Filament\Resources\SalaryAdvanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalaryAdvances extends ListRecords
{
    protected static string $resource = SalaryAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
