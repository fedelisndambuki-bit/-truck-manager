<?php

namespace App\Filament\Resources\MaterialRequestResource\Pages;

use App\Filament\Resources\MaterialRequestResource;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateMaterialRequest extends CreateRecord
{
    use HasWizard;

    protected static string $resource = MaterialRequestResource::class;

    protected function hasSkippableSteps(): bool
    {
        return true;
    }

    public function getSteps(): array
    {
        return [
            Step::make('Project / Site & Priority')
                ->icon('heroicon-o-building-office-2')
                ->schema(MaterialRequestResource::projectSiteSchema())
                ->columns(2),
            Step::make('Material List')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema(MaterialRequestResource::itemsSchema()),
            Step::make('Purpose & Notes')
                ->icon('heroicon-o-document-text')
                ->schema(MaterialRequestResource::purposeSchema()),
            Step::make('Approval')
                ->icon('heroicon-o-check-badge')
                ->schema(MaterialRequestResource::approvalSchema()),
        ];
    }
}
