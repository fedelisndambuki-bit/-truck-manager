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
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
            'title' => 'Invoices',
            'subtitle' => 'Track and manage all client invoices for your fleet.',
            'stats' => [
                [
                    'label' => 'Total Invoiced',
                    'value' => $currency . ' ' . number_format($totalInvoiced, 2),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    'sub' => Invoice::count() . ' invoices total',
                ],
                [
                    'label' => 'Paid',
                    'value' => $currency . ' ' . number_format($paid, 2),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    'color' => 'success',
                    'sub' => Invoice::where('status', 'paid')->count() . ' invoices',
                ],
                [
                    'label' => 'Outstanding',
                    'value' => $currency . ' ' . number_format($outstanding, 2),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    'color' => 'warning',
                    'sub' => Invoice::whereIn('status', ['sent', 'overdue'])->count() . ' invoices',
                ],
                [
                    'label' => 'Overdue',
                    'value' => $currency . ' ' . number_format($overdue, 2),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />',
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
