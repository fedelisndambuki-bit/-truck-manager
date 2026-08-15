<?php

namespace App\Filament\Resources\MedicalCaseResource\Pages;

use App\Filament\Resources\MedicalCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListMedicalCases extends ListRecords
{
    protected static string $resource = MedicalCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-heart',
            'title' => 'Medical Cases',
            'subtitle' => 'Track employee medical cases and treatment progress.',
        ]);
    }
}
