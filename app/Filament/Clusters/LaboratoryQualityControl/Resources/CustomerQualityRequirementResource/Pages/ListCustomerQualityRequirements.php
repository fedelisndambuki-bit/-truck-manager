<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\CustomerQualityRequirementResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\CustomerQualityRequirementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCustomerQualityRequirements extends ListRecords
{
    protected static string $resource = CustomerQualityRequirementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-identification',
            'title' => 'Customer Quality Requirements',
            'subtitle' => 'Define and track customer-specific quality requirements.',
        ]);
    }
}
