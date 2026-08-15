<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TopClientsWidget extends BaseWidget
{
    protected ?string $heading = 'Top Clients by Revenue';

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $companyId = auth()->user()?->company_id;

        $topClients = Invoice::query()
            ->select('client_id', DB::raw('SUM(total_amount) as total_revenue'))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'paid')
            ->groupBy('client_id')
            ->orderByDesc('total_revenue')
            ->limit(3)
            ->with('client')
            ->get();

        if ($topClients->isEmpty()) {
            return [
                Stat::make('No paid invoices yet', '—')
                    ->description('Revenue data will appear here')
                    ->color('gray'),
            ];
        }

        $currency = session('currency', 'KES');

        return $topClients->map(fn ($row, $index) => Stat::make(
            ($index + 1) . '. ' . ($row->client?->company_name ?? 'Unknown'),
            $currency . ' ' . number_format($row->total_revenue, 2)
        )
            ->description('Total paid revenue')
            ->descriptionIcon('heroicon-s-building-office')
            ->color('success')
        )->toArray();
    }
}
