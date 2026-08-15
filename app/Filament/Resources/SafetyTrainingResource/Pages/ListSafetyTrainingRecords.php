<?php

namespace App\Filament\Resources\SafetyTrainingResource\Pages;

use App\Filament\Resources\SafetyTrainingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSafetyTrainingRecords extends ListRecords
{
    protected static string $resource = SafetyTrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-academic-cap',
            'title' => 'Safety Training Records',
            'subtitle' => 'Track employee safety training completion and certifications.',
        ]);
    }
}
