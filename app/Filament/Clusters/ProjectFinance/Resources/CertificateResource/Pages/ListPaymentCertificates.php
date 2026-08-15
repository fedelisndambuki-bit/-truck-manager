<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\CertificateResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\CertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;

    protected static string $view = 'filament.clusters.project-finance.resources.certificate-resource.pages.list-payment-certificates';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
