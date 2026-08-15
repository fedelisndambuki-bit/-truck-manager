<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\SamplingRequestResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\SamplingRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSamplingRequests extends ListRecords
{
    protected static string $resource = SamplingRequestResource::class;

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
            'title' => 'Sampling Requests',
            'subtitle' => 'Manage requests for laboratory sampling and inspection.',
        ]);
    }
}
