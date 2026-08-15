<?php

namespace App\Filament\Resources\SalaryAdvanceResource\Pages;

use App\Filament\Resources\SalaryAdvanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalaryAdvance extends EditRecord
{
    protected static string $resource = SalaryAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
