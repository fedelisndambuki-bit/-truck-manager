<?php

namespace App\Filament\Resources\BrandSettingResource\Pages;

use App\Filament\Resources\BrandSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBrandSetting extends EditRecord
{
    protected static string $resource = BrandSettingResource::class;

    protected static string $view = 'filament.resources.brand-setting-resource.pages.edit-brand-setting';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

