<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastComplianceResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastComplianceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBlastCompliances extends ListRecords
{
    protected static string $resource = BlastComplianceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-check-badge',
            'title' => 'Blast Compliance Records',
            'subtitle' => 'Track regulatory compliance records for blasting operations.',
        ]);
    }
}
