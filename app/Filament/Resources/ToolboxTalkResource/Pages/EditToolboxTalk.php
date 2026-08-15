<?php

namespace App\Filament\Resources\ToolboxTalkResource\Pages;

use App\Filament\Resources\ToolboxTalkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditToolboxTalk extends EditRecord
{
    protected static string $resource = ToolboxTalkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
