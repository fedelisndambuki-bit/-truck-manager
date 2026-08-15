<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastPlanResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBlastPlans extends ListRecords
{
    protected static string $resource = BlastPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-list',
            'title' => 'Blast Plans',
            'subtitle' => 'Plan and manage blast designs prior to execution.',
        ]);
    }
}
