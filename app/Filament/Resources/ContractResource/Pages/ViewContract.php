<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    protected static string $view = 'filament.resources.contract-resource.pages.view-contract';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('printContract')
                ->label('Print Contract')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => $this->streamContractPdf()),
        ];
    }

    public function getScopeOfWorks()
    {
        return $this->record->scopeOfWorks()->with('items')->get();
    }

    public function getAssignments()
    {
        return $this->record->assignments()->with(['site', 'siteManager', 'supervisor'])->get();
    }

    public function getWorkOrders()
    {
        return $this->record->workOrders()->with(['project', 'site', 'supervisor'])->latest('created_at')->get();
    }

    public function getProgressUpdates()
    {
        return $this->record->progressUpdates()->with('milestone')->latest('date')->get();
    }

    public function getVariations()
    {
        return $this->record->variations()->latest('created_at')->get();
    }

    public function getPaymentCertificates()
    {
        return $this->record->paymentCertificates()->latest('created_at')->get();
    }

    protected function streamContractPdf()
    {
        $record = $this->record->load(['subcontractor', 'project', 'site']);

        $pdf = Pdf::loadView('reports.contract', [
            'title' => 'Contract — ' . $record->contract_number,
            'date' => now()->format('d M Y'),
            'contract' => $record,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'contract-' . $record->contract_number . '.pdf');
    }
}
