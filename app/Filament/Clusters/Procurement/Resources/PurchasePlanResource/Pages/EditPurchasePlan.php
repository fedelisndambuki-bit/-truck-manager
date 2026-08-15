<?php

namespace App\Filament\Clusters\Procurement\Resources\PurchasePlanResource\Pages;

use App\Filament\Clusters\Procurement\Resources\PurchasePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchasePlan extends EditRecord
{
    protected static string $resource = PurchasePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
