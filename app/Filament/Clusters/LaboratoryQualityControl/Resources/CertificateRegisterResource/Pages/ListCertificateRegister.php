<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\CertificateRegisterResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\CertificateRegisterResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCertificateRegister extends ListRecords
{
    protected static string $resource = CertificateRegisterResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-archive-box',
            'title' => 'Certificate Register',
            'subtitle' => 'Maintain a central register of all quality certificates on file.',
        ]);
    }
}
