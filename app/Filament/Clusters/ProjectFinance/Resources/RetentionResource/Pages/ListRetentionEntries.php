<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\RetentionResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\RetentionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListRetentionEntries extends ListRecords
{
    protected static string $resource = RetentionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-lock-closed',
            'title' => 'Retention Management',
            'subtitle' => 'Track retained contract funds and manage their release schedule.',
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Clusters\ProjectFinance\Widgets\RetentionChart::class,
        ];
    }
}
