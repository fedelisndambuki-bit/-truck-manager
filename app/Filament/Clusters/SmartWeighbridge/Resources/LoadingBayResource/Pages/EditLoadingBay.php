<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\LoadingBayResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\LoadingBayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoadingBay extends EditRecord
{
    protected static string $resource = LoadingBayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
