<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\ProofOfDeliveryResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\ProofOfDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListProofOfDeliveries extends ListRecords
{
    protected static string $resource = ProofOfDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-check',
            'title' => 'Proof of Deliveries',
            'subtitle' => 'Review signed confirmations and evidence for completed deliveries.',
        ]);
    }
}
