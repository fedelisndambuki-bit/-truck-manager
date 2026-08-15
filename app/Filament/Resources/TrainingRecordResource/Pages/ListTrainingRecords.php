<?php

namespace App\Filament\Resources\TrainingRecordResource\Pages;

use App\Filament\Resources\TrainingRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListTrainingRecords extends ListRecords
{
    protected static string $resource = TrainingRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Training'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-academic-cap',
            'title' => 'Training Records',
            'subtitle' => 'Track staff training sessions and completion status.',
        ]);
    }
}
