<?php

namespace App\Filament\Resources\SubcontractorPrequalificationResource\Pages;

use App\Filament\Resources\SubcontractorPrequalificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSubcontractorPrequalifications extends ListRecords
{
    protected static string $resource = SubcontractorPrequalificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Assessment'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-check',
            'title' => 'Subcontractor Prequalifications',
            'subtitle' => 'Assess and manage subcontractor prequalification applications.',
        ]);
    }
}
