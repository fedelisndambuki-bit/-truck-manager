<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\CertificateResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\CertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-check',
            'title' => 'Certificates',
            'subtitle' => 'Manage certificates of analysis and compliance documentation.',
        ]);
    }
}
