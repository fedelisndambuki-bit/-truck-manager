<?php

namespace App\Filament\Resources\PaymentCertificateResource\Pages;

use App\Filament\Resources\PaymentCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentCertificates extends ListRecords
{
    protected static string $resource = PaymentCertificateResource::class;

    protected static string $view = 'filament.resources.payment-certificate-resource.pages.list-payment-certificates';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
