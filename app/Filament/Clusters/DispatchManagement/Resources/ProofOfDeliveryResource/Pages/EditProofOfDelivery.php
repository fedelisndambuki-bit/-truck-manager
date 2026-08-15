<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\ProofOfDeliveryResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\ProofOfDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProofOfDelivery extends EditRecord
{
    protected static string $resource = ProofOfDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
