<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\ProofOfDeliveryResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\ProofOfDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProofOfDeliveries extends ListRecords
{
    protected static string $resource = ProofOfDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
