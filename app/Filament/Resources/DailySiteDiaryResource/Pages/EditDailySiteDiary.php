<?php

namespace App\Filament\Resources\DailySiteDiaryResource\Pages;

use App\Filament\Resources\DailySiteDiaryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailySiteDiary extends EditRecord
{
    protected static string $resource = DailySiteDiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
