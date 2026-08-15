<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateContract extends CreateRecord
{
    use HasWizard;

    protected static string $resource = ContractResource::class;

    protected function hasSkippableSteps(): bool
    {
        return true;
    }

    public function getSteps(): array
    {
        return [
            Step::make('Subcontractor & Project')
                ->icon('heroicon-o-building-office-2')
                ->schema(ContractResource::subcontractorProjectSchema())
                ->columns(2),
            Step::make('Scope Summary')
                ->icon('heroicon-o-document-text')
                ->schema(ContractResource::scopeSummarySchema()),
            Step::make('Value & Retention')
                ->icon('heroicon-o-banknotes')
                ->schema(ContractResource::valueRetentionSchema())
                ->columns(2),
            Step::make('Dates & Terms')
                ->icon('heroicon-o-calendar-days')
                ->schema(ContractResource::datesTermsSchema())
                ->columns(2),
            Step::make('Bond & Insurance')
                ->icon('heroicon-o-shield-check')
                ->schema(ContractResource::bondInsuranceSchema())
                ->columns(2),
        ];
    }
}
