<?php

namespace App\Filament\Resources\SubcontractorAssignmentResource\Pages;

use App\Filament\Resources\SubcontractorAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSubcontractorAssignments extends ListRecords
{
    protected static string $resource = SubcontractorAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Assignment'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-map',
            'title' => 'Subcontractor Assignments',
            'subtitle' => 'Manage subcontractor assignments across active projects.',
        ]);
    }
}
