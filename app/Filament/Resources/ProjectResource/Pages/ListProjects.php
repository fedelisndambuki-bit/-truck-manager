<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-building-office-2',
            'title' => 'Projects',
            'subtitle' => 'Oversee all active and completed construction projects.',
        ]);
    }
}
