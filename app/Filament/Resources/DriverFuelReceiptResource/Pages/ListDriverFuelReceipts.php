<?php

namespace App\Filament\Resources\DriverFuelReceiptResource\Pages;

use App\Filament\Resources\DriverFuelReceiptResource;
use Filament\Resources\Pages\ListRecords;

class ListDriverFuelReceipts extends ListRecords
{
    protected static string $resource = DriverFuelReceiptResource::class;

    protected static string $view = 'filament.resources.driver-fuel-receipt-resource.pages.list-driver-fuel-receipts';
}
