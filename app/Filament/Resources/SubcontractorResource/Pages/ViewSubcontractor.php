<?php

namespace App\Filament\Resources\SubcontractorResource\Pages;

use App\Filament\Resources\SubcontractorResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSubcontractor extends ViewRecord
{
    protected static string $resource = SubcontractorResource::class;

    protected static string $view = 'filament.resources.subcontractor-resource.pages.view-subcontractor';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getPrequalifications()
    {
        return $this->record->prequalifications()->latest('created_at')->get();
    }

    public function getContracts()
    {
        return $this->record->contracts()->with('project')->latest('created_at')->get();
    }

    public function getAssignments()
    {
        return $this->record->assignments()->with(['project', 'site', 'siteManager', 'supervisor'])->get();
    }

    public function getWorkOrders()
    {
        return $this->record->workOrders()->with(['project', 'contract', 'supervisor'])->latest('created_at')->get();
    }

    public function getVariations()
    {
        return \App\Models\Variation::whereIn('contract_id', $this->record->contracts()->pluck('id'))
            ->with('contract')
            ->latest('created_at')
            ->get();
    }

    public function getPaymentCertificates()
    {
        return \App\Models\PaymentCertificate::whereIn('contract_id', $this->record->contracts()->pluck('id'))
            ->with('contract')
            ->latest('created_at')
            ->get();
    }

    public function getDocuments()
    {
        return $this->record->documents()->latest('created_at')->get();
    }

    public function getPerformanceReviews()
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('subcontractor_performance_reviews')) {
            return collect();
        }

        return $this->record->performanceReviews()->latest('review_date')->get();
    }
}
