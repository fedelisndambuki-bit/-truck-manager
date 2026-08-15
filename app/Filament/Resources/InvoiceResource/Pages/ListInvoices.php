<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\Widgets\InvoiceHeaderWidget;
use App\Filament\Resources\InvoiceResource\Widgets\InvoiceFilterWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.resources.invoice-resource.pages.list-invoices';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // Widgets are rendered manually in the custom Blade view to control layout.

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['client', 'truck', 'driver']); // Eager load relationships
    }
}
