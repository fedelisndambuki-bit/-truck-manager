<?php

namespace App\Filament\Clusters\ProductionManagement\Pages;

use App\Filament\Clusters\ProductionManagement;
use App\Models\ExtractionSite;
use App\Models\ProductionOutput;
use App\Models\ProductionSetting;
use App\Models\ProductionStockTransfer;
use App\Models\Stockpile;
use App\Models\WeighbridgeTicket;
use App\Traits\HasModuleAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Daily stock reconciliation — computed live from the single source of
 * truth (Stockpile.quantity_available) plus today's Production/Transfers/
 * Dispatches, rather than a second parallel ledger. Historical dates aren't
 * supported without a stock-movement ledger (not built for this pass), so
 * this always reflects "as of now" for the selected quarry.
 */
class DailyProduction extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'production.view';
    }

    protected static ?string $cluster = ProductionManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Daily Production';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Daily Production Reconciliation';

    protected static string $view = 'filament.clusters.production-management.pages.daily-production';

    public ?int $extraction_site_id = null;

    public string $remarks = '';

    public function mount(): void
    {
        $companyId = Auth::user()?->company_id;
        $this->extraction_site_id = ExtractionSite::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->value('id');
        $this->loadRemarks();
    }

    public function updatedExtractionSiteId(): void
    {
        $this->loadRemarks();
    }

    protected function loadRemarks(): void
    {
        $companyId = Auth::user()?->company_id;
        $this->remarks = $companyId && $this->extraction_site_id
            ? (string) ProductionSetting::getSetting((int) $companyId, $this->remarksKey(), '')
            : '';
    }

    protected function remarksKey(): string
    {
        return 'daily_production_remarks_' . $this->extraction_site_id . '_' . now()->toDateString();
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />',
            'title' => 'Daily Production',
            'subtitle' => 'Live stock reconciliation — Opening, Production, Transfers, Dispatches and Closing stock as of now.',
        ]);
    }

    public function getSiteOptions(): array
    {
        $companyId = Auth::user()?->company_id;

        return ExtractionSite::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->pluck('name', 'id')->all();
    }

    public function saveRemarks(): void
    {
        $companyId = Auth::user()?->company_id;

        if ($companyId && $this->extraction_site_id) {
            ProductionSetting::setSetting((int) $companyId, $this->remarksKey(), $this->remarks);
        }

        Notification::make()->title('Remarks saved')->success()->send();
    }

    public function getReconciliation(): array
    {
        if (! $this->extraction_site_id) {
            return [];
        }

        $companyId = Auth::user()?->company_id;

        return Stockpile::query()
            ->where('extraction_site_id', $this->extraction_site_id)
            ->with('material')
            ->get()
            ->map(function (Stockpile $stockpile) use ($companyId) {
                $production = (float) ProductionOutput::query()
                    ->where('stockpile_id', $stockpile->id)
                    ->whereDate('created_at', today())
                    ->whereHas('productionLog', fn ($q) => $q->where('status', 'Approved'))
                    ->sum('quantity_produced');

                $transfersIn = (float) ProductionStockTransfer::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->where('destination_stockpile_id', $stockpile->id)
                    ->where('status', 'Completed')
                    ->whereDate('updated_at', today())
                    ->sum('quantity');

                $transfersOut = (float) ProductionStockTransfer::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->where('source_stockpile_id', $stockpile->id)
                    ->where('status', 'Completed')
                    ->whereDate('updated_at', today())
                    ->sum('quantity');

                $dispatches = (float) WeighbridgeTicket::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->where('material_id', $stockpile->material_id)
                    ->where('extraction_site_id', $this->extraction_site_id)
                    ->where('direction', 'Outbound')
                    ->where('status', 'Weighed')
                    ->whereDate('created_at', today())
                    ->sum('net_weight') / 1000;

                $closing = (float) $stockpile->quantity_available;
                $opening = $closing - $production + $dispatches - $transfersIn + $transfersOut;

                return [
                    'material' => $stockpile->material?->name ?? 'Unknown',
                    'opening' => round($opening, 2),
                    'production' => round($production, 2),
                    'transfers_in' => round($transfersIn, 2),
                    'transfers_out' => round($transfersOut, 2),
                    'dispatches' => round($dispatches, 2),
                    'closing' => round($closing, 2),
                ];
            })
            ->values()
            ->all();
    }
}
