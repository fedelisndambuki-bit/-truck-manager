<?php

namespace App\Filament\Clusters\Procurement\Resources\FrameworkAgreementResource\Pages;

use App\Filament\Clusters\Procurement\Resources\FrameworkAgreementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListFrameworkAgreements extends ListRecords
{
    protected static string $resource = FrameworkAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-scale',
            'title' => 'Framework Agreements',
            'subtitle' => 'Track long-term negotiated-price agreements with suppliers.',
        ]);
    }
}
