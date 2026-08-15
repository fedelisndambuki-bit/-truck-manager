<?php

namespace App\Filament\Resources\CorrectiveActionResource\Pages;

use App\Filament\Resources\CorrectiveActionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCorrectiveActions extends ListRecords
{
    protected static string $resource = CorrectiveActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
