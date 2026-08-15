<?php

namespace App\Filament\SuperAdmin\Resources\PlatformSettingResource\Pages;

use App\Filament\SuperAdmin\Resources\PlatformSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlatformSetting extends EditRecord
{
    protected static string $resource = PlatformSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
