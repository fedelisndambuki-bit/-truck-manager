<?php

namespace App\Filament\Resources\PutAwayTaskResource\Pages;

use App\Filament\Resources\PutAwayTaskResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPutAwayTasks extends ListRecords
{
    protected static string $resource = PutAwayTaskResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-inbox-arrow-down',
            'title' => 'Put-Away Tasks',
            'subtitle' => 'Track and complete tasks for putting received materials into warehouse bins.',
        ]);
    }
}
