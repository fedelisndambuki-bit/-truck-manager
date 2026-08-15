<?php

namespace App\Filament\Resources\ToolboxTalkResource\Pages;

use App\Filament\Resources\ToolboxTalkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListToolboxTalks extends ListRecords
{
    protected static string $resource = ToolboxTalkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-megaphone',
            'title' => 'Toolbox Talks',
            'subtitle' => 'Record toolbox talks conducted with field teams.',
        ]);
    }
}
