<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected static string $view = 'filament.resources.purchase-order-resource.pages.view-purchase-order';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('printPo')
                ->label('Print PO')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => $this->streamPurchaseOrderPdf()),
        ];
    }

    protected function streamPurchaseOrderPdf()
    {
        $record = $this->record->load(['supplier', 'items.material']);

        $pdf = Pdf::loadView('reports.purchase-order', [
            'title' => 'Purchase Order — ' . $record->po_number,
            'date' => now()->format('d M Y'),
            'order' => $record,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'purchase-order-' . $record->po_number . '.pdf');
    }

    public function getItems()
    {
        return $this->record->items()->with('material')->get();
    }
}
