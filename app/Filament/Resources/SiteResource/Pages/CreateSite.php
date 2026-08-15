<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateSite extends CreateRecord
{
    use HasWizard;

    protected static string $resource = SiteResource::class;

    protected function hasSkippableSteps(): bool
    {
        return true;
    }

    public function getSteps(): array
    {
        return [
            Step::make('Basic Info')
                ->icon('heroicon-o-information-circle')
                ->schema(SiteResource::basicInfoSchema())
                ->columns(2),
            Step::make('Location')
                ->icon('heroicon-o-map-pin')
                ->schema(SiteResource::locationSchema())
                ->columns(2),
            Step::make('Team')
                ->icon('heroicon-o-user-group')
                ->schema(SiteResource::teamSchema())
                ->columns(2),
            Step::make('Operations')
                ->icon('heroicon-o-cog-6-tooth')
                ->schema(SiteResource::operationsSchema())
                ->columns(2),
            Step::make('Safety')
                ->icon('heroicon-o-shield-check')
                ->schema(SiteResource::safetySchema())
                ->columns(2),
            Step::make('Documents')
                ->icon('heroicon-o-document-text')
                ->schema(SiteResource::documentsSchema())
                ->columns(2),
        ];
    }

    protected function afterCreate(): void
    {
        $state = $this->form->getState();

        SiteResource::syncTeamFromFormState($this->record, $state['team'] ?? []);
        SiteResource::syncDocumentsFromFormState($this->record, $state['documents'] ?? []);
    }
}
