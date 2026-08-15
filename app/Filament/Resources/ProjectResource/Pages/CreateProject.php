<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateProject extends CreateRecord
{
    use HasWizard;

    protected static string $resource = ProjectResource::class;

    protected function hasSkippableSteps(): bool
    {
        return true;
    }

    public function getSteps(): array
    {
        return [
            Step::make('Basic Info')
                ->icon('heroicon-o-information-circle')
                ->schema(ProjectResource::basicInfoSchema())
                ->columns(2),
            Step::make('Client')
                ->icon('heroicon-o-briefcase')
                ->schema(ProjectResource::clientInfoSchema())
                ->columns(2),
            Step::make('Location')
                ->icon('heroicon-o-map-pin')
                ->schema(ProjectResource::locationSchema())
                ->columns(2),
            Step::make('Team')
                ->icon('heroicon-o-user-group')
                ->schema(ProjectResource::teamSchema())
                ->columns(2),
            Step::make('Financials')
                ->icon('heroicon-o-banknotes')
                ->schema(ProjectResource::financialSchema())
                ->columns(2),
            Step::make('Timeline')
                ->icon('heroicon-o-calendar')
                ->schema(ProjectResource::timelineSchema())
                ->columns(2),
            Step::make('Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->schema(ProjectResource::settingsSchema())
                ->columns(2),
            Step::make('Documents')
                ->icon('heroicon-o-document-text')
                ->schema(ProjectResource::documentsSchema())
                ->columns(2),
        ];
    }

    protected function afterCreate(): void
    {
        $state = $this->form->getState();

        ProjectResource::syncTeamFromFormState($this->record, $state['team'] ?? []);
        ProjectResource::syncDocumentsFromFormState($this->record, $state['documents'] ?? []);
    }
}
