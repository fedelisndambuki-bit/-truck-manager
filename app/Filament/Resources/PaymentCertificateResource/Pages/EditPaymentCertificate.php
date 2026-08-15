<?php

namespace App\Filament\Resources\PaymentCertificateResource\Pages;

use App\Filament\Resources\PaymentCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentCertificate extends EditRecord
{
    protected static string $resource = PaymentCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
