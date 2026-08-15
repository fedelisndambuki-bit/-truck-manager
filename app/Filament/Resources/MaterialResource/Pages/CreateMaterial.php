<?php

namespace App\Filament\Resources\MaterialResource\Pages;

use App\Filament\Resources\MaterialResource;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateMaterial extends CreateRecord
{
    use HasWizard;

    protected static string $resource = MaterialResource::class;

    protected function hasSkippableSteps(): bool
    {
        return true;
    }

    public function getSteps(): array
    {
        return [
            Step::make('Basic Info')
                ->icon('heroicon-o-information-circle')
                ->schema(MaterialResource::basicInfoSchema())
                ->columns(2),
            Step::make('Supplier & Cost')
                ->icon('heroicon-o-truck')
                ->schema(MaterialResource::supplierCostSchema())
                ->columns(2),
            Step::make('Stock Levels')
                ->icon('heroicon-o-archive-box')
                ->schema(MaterialResource::stockLevelsSchema())
                ->columns(2),
            Step::make('Specs & Documents')
                ->icon('heroicon-o-document-text')
                ->schema(MaterialResource::specsDocumentsSchema())
                ->columns(2),
            Step::make('Identification')
                ->icon('heroicon-o-qr-code')
                ->schema(MaterialResource::identificationSchema())
                ->columns(2),
        ];
    }
}
