<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\CertificateResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\CertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCertificate extends EditRecord
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
