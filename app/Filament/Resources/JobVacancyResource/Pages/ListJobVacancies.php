<?php

namespace App\Filament\Resources\JobVacancyResource\Pages;

use App\Filament\Resources\JobVacancyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJobVacancies extends ListRecords
{
    protected static string $resource = JobVacancyResource::class;

    protected static string $view = 'filament.resources.job-vacancy-resource.pages.list-job-vacancies';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Vacancy'),
        ];
    }
}
