<?php

namespace App\Filament\Resources\DriverFuelReceiptResource\Pages;

use App\Filament\Resources\DriverFuelReceiptResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDriverFuelReceipts extends ListRecords
{
    protected static string $resource = DriverFuelReceiptResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-receipt-percent',
            'title' => 'Driver Fuel Receipts',
            'subtitle' => 'Review fuel receipts submitted by drivers.',
        ]);
    }
}
