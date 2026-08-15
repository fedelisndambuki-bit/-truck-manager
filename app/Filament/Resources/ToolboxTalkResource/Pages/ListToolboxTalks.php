<?php

namespace App\Filament\Resources\ToolboxTalkResource\Pages;

use App\Filament\Resources\ToolboxTalkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListToolboxTalks extends ListRecords
{
    protected static string $resource = ToolboxTalkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
