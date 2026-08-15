<?php

namespace App\Filament\Clusters\DocumentManagement\Widgets;

use App\Services\Construction\DocumentManagementService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DocumentKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $service = app(DocumentManagementService::class);

        $expired = $service->expiredDocuments($companyId);
        $contractsExpiring = $service->contractsExpiring($companyId);
        $rfisOpen = $service->rfisOpen($companyId);

        return [
            Stat::make('Total Documents', (string) $service->totalDocuments($companyId))
                ->descriptionIcon('heroicon-m-document-text')->color('info'),
            Stat::make('New Documents Today', (string) $service->newDocumentsToday($companyId))
                ->descriptionIcon('heroicon-m-document-plus')->color('success'),
            Stat::make('Pending Approvals', (string) $service->pendingApprovals($companyId))
                ->descriptionIcon('heroicon-m-clock')->color('warning'),
            Stat::make('Documents Under Review', (string) $service->documentsUnderReview($companyId))
                ->descriptionIcon('heroicon-m-magnifying-glass')->color('warning'),
            Stat::make('RFIs Open', (string) $rfisOpen)
                ->descriptionIcon('heroicon-m-question-mark-circle')->color($rfisOpen > 0 ? 'warning' : 'success'),
            Stat::make('Drawing Revisions', (string) $service->drawingRevisions($companyId))
                ->descriptionIcon('heroicon-m-pencil-square')->color('info'),
            Stat::make('Expired Documents', (string) $expired)
                ->descriptionIcon('heroicon-m-exclamation-triangle')->color($expired > 0 ? 'danger' : 'success'),
            Stat::make('Contracts Expiring', (string) $contractsExpiring)
                ->descriptionIcon('heroicon-m-calendar')->color($contractsExpiring > 0 ? 'danger' : 'success'),
            Stat::make('Documents Shared Today', (string) $service->documentsSharedToday($companyId))
                ->descriptionIcon('heroicon-m-share')->color('info'),
            Stat::make('Storage Used', $service->storageUsedForHumans($companyId))
                ->descriptionIcon('heroicon-m-server')->color('gray'),
        ];
    }
}
