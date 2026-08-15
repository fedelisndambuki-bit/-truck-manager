<?php

namespace App\Filament\Resources\DailySiteDiaryResource\Pages;

use App\Filament\Resources\DailySiteDiaryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailySiteDiaries extends ListRecords
{
    protected static string $resource = DailySiteDiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
