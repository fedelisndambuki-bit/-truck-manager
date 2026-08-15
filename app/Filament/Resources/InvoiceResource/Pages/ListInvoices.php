<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\Widgets\InvoiceFilterWidget;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        $currency = session('currency', 'KES');

        $totalInvoiced = Invoice::sum('total_amount');
        $paid = Invoice::where('status', 'paid')->sum('total_amount');
        $outstanding = Invoice::whereIn('status', ['sent', 'overdue'])->sum('total_amount');
        $overdue = Invoice::where('status', 'overdue')->sum('total_amount');

        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-text',
            'title' => 'Invoices',
            'subtitle' => 'Track and manage all client invoices for your fleet.',
            'stats' => [
                [
                    'label' => 'Total Invoiced',
                    'value' => $currency . ' ' . number_format($totalInvoiced, 2),
                    'icon' => 'heroicon-o-banknotes',
                    'sub' => Invoice::count() . ' invoices total',
                ],
                [
                    'label' => 'Paid',
                    'value' => $currency . ' ' . number_format($paid, 2),
                    'icon' => 'heroicon-o-check-circle',
                    'color' => 'success',
                    'sub' => Invoice::where('status', 'paid')->count() . ' invoices',
                ],
                [
                    'label' => 'Outstanding',
                    'value' => $currency . ' ' . number_format($outstanding, 2),
                    'icon' => 'heroicon-o-clock',
                    'color' => 'warning',
                    'sub' => Invoice::whereIn('status', ['sent', 'overdue'])->count() . ' invoices',
                ],
                [
                    'label' => 'Overdue',
                    'value' => $currency . ' ' . number_format($overdue, 2),
                    'icon' => 'heroicon-o-exclamation-circle',
                    'color' => 'danger',
                    'sub' => Invoice::where('status', 'overdue')->count() . ' invoices',
                ],
            ],
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InvoiceFilterWidget::class,
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with(['client', 'truck', 'driver']); // Eager load relationships
    }
}
