<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\CertificateResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\CertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPaymentCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-check',
            'title' => 'Payment Certificates',
            'subtitle' => 'Issue and track payment certificates for approved project work.',
        ]);
    }
}
