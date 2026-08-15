<?php

namespace App\Filament\Resources\SubcontractorResource\Pages;

use App\Filament\Resources\SubcontractorResource;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateSubcontractor extends CreateRecord
{
    use HasWizard;

    protected static string $resource = SubcontractorResource::class;

    protected function hasSkippableSteps(): bool
    {
        return true;
    }

    public function getSteps(): array
    {
        return [
            Step::make('Company Profile')
                ->icon('heroicon-o-building-office')
                ->schema(SubcontractorResource::companyProfileSchema())
                ->columns(2),
            Step::make('Contact & Location')
                ->icon('heroicon-o-map-pin')
                ->schema(SubcontractorResource::contactLocationSchema())
                ->columns(2),
            Step::make('Bank & Insurance')
                ->icon('heroicon-o-shield-check')
                ->schema(SubcontractorResource::bankInsuranceSchema())
                ->columns(2),
            Step::make('Compliance Dates')
                ->icon('heroicon-o-calendar-days')
                ->schema(SubcontractorResource::complianceDatesSchema())
                ->columns(2),
            Step::make('Status')
                ->icon('heroicon-o-check-badge')
                ->schema(SubcontractorResource::statusSchema()),
        ];
    }
}
