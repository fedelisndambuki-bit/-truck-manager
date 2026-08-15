<?php

namespace App\Filament\Resources\PaymentCertificateResource\Pages;

use App\Filament\Resources\PaymentCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPaymentCertificates extends ListRecords
{
    protected static string $resource = PaymentCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-banknotes',
            'title' => 'Payment Certificates',
            'subtitle' => 'Manage and track payment certificates issued to subcontractors.',
        ]);
    }
}
