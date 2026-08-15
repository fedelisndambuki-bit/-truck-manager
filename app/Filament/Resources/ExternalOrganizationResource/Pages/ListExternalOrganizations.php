<?php

namespace App\Filament\Resources\ExternalOrganizationResource\Pages;

use App\Filament\Resources\ExternalOrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListExternalOrganizations extends ListRecords
{
    protected static string $resource = ExternalOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-building-office',
            'title' => 'External Organizations',
            'subtitle' => 'Manage consultants, contractors, and other external organizations linked to your projects.',
        ]);
    }
}
