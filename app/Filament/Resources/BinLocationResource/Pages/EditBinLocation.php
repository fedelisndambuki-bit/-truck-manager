<?php

namespace App\Filament\Resources\BinLocationResource\Pages;

use App\Filament\Resources\BinLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBinLocation extends EditRecord
{
    protected static string $resource = BinLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
