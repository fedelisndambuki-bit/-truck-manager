<?php

namespace App\Filament\Resources\MaterialRequestResource\Pages;

use App\Filament\Resources\MaterialRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListMaterialRequests extends ListRecords
{
    protected static string $resource = MaterialRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Request'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-list',
            'title' => 'Material Requests',
            'subtitle' => 'Manage material requests submitted from sites and projects.',
        ]);
    }
}
