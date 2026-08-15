<?php

namespace App\Filament\Clusters\ProjectFinance\Widgets;

use App\Filament\Clusters\ProjectFinance\Resources\BudgetResource;
use App\Filament\Clusters\ProjectFinance\Resources\CertificateResource;
use App\Filament\Clusters\ProjectFinance\Resources\ChangeOrderResource;
use App\Filament\Clusters\ProjectFinance\Resources\ClientInvoiceResource;
use App\Filament\Clusters\ProjectFinance\Resources\ProgressClaimResource;
use App\Filament\Clusters\ProjectFinance\Pages\Reports;
use Filament\Widgets\Widget;

class FinanceQuickActionsWidget extends Widget
{
    // Reuses the generic Construction dashboard's quick-actions view — it just
    // loops getActions(), so no need for a second near-identical Blade file.
    protected static string $view = 'filament.widgets.quick-actions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getActions(): array
    {
        return [
            ['label' => 'Create Budget', 'icon' => 'heroicon-o-calculator', 'url' => BudgetResource::getUrl('create')],
            ['label' => 'Approve Budgets', 'icon' => 'heroicon-o-check-circle', 'url' => BudgetResource::getUrl('index', ['tableFilters[status][value]' => 'Submitted'])],
            ['label' => 'Generate Invoice', 'icon' => 'heroicon-o-receipt-percent', 'url' => ClientInvoiceResource::getUrl('create')],
            ['label' => 'Submit Progress Claim', 'icon' => 'heroicon-o-document-check', 'url' => ProgressClaimResource::getUrl('create')],
            ['label' => 'Approve Variation', 'icon' => 'heroicon-o-arrows-right-left', 'url' => ChangeOrderResource::getUrl('index')],
            ['label' => 'Record Payment', 'icon' => 'heroicon-o-banknotes', 'url' => CertificateResource::getUrl('create')],
            ['label' => 'Generate Financial Report', 'icon' => 'heroicon-o-document-chart-bar', 'url' => Reports::getUrl()],
            ['label' => 'Export Data', 'icon' => 'heroicon-o-arrow-down-tray', 'url' => Reports::getUrl()],
        ];
    }
}
