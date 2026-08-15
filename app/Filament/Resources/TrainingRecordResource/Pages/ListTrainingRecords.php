<?php

namespace App\Filament\Resources\TrainingRecordResource\Pages;

use App\Filament\Resources\TrainingRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrainingRecords extends ListRecords
{
    protected static string $resource = TrainingRecordResource::class;

    protected static string $view = 'filament.resources.training-record-resource.pages.list-training-records';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Training'),
        ];
    }
}
