<?php

namespace App\Filament\Clusters\Procurement\Resources\PurchaseOrderResource\Pages;

use App\Filament\Clusters\Procurement\Resources\PurchaseOrderResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Purchase Order')->schema([
                TextEntry::make('po_number')->label('PO #'),
                TextEntry::make('supplier.company_name')->label('Supplier'),
                TextEntry::make('status')->badge()->color(fn (string $state): string => PurchaseOrderResource::getStatusColor($state)),
                TextEntry::make('total_amount')->money(fn ($record) => $record->currency ?: 'KES'),
                TextEntry::make('expected_delivery')->date()->placeholder('—'),
                TextEntry::make('payment_terms')->placeholder('—'),
            ])->columns(3),
        ]);
    }
}
