<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\DrawingResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\DrawingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDrawings extends ListRecords
{
    protected static string $resource = DrawingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-pencil-square',
            'title' => 'Drawings',
            'subtitle' => 'Manage engineering drawings and track their revisions and approvals.',
        ]);
    }
}
