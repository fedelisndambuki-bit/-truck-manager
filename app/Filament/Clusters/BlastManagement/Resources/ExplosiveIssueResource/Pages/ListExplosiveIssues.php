<?php

namespace App\Filament\Clusters\BlastManagement\Resources\ExplosiveIssueResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\ExplosiveIssueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListExplosiveIssues extends ListRecords
{
    protected static string $resource = ExplosiveIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-beaker',
            'title' => 'Explosive Issues',
            'subtitle' => 'Track issuance and usage of explosives for blasting operations.',
        ]);
    }
}
