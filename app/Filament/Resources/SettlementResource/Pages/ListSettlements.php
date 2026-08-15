<?php

namespace App\Filament\Resources\SettlementResource\Pages;

use App\Filament\Resources\SettlementResource;
use App\Filament\Resources\SettlementResource\Widgets\SettlementFilterWidget;
use App\Models\Settlement;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On; // Added for notifications

class ListSettlements extends ListRecords
{
    protected static string $resource = SettlementResource::class;


    public array $filters = []; // Property to hold filters




    public function mount(): void
    {
        // Initialize filters from request if present
        $this->filters = request()->query();
        parent::mount();
    }

    #[On('applySettlementFilters')]
    public function applySettlementFilters(array $filters): void
    {
        $this->filters = $filters;
        $this->resetPage(); // Reset pagination when filters change
    }

    protected function getTableQuery(): Builder
    {
        $query = Settlement::query()
            ->when($this->filters['date_from'] ?? null, fn ($q, $date) => $q->where('settlement_date', '>=', $date))
            ->when($this->filters['date_to'] ?? null, fn ($q, $date) => $q->where('settlement_date', '<=', $date))
            ->when($this->filters['settlement_number'] ?? null, fn ($q, $number) => $q->where('settlement_id', 'like', "%{$number}%"))
            ->when($this->filters['property'] ?? null, fn ($q, $property) => $q->where('trucks', 'like', "%{$property}%"))
            ->when($this->filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));

        // Add sorting (default from original controller was latest('settlement_date'))
        $query->latest('settlement_date');

        return $query;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Only keeping CreateAction, other actions will be in custom view's buttons
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        $currency = session('currency', 'KES');

        $totalEarned = Settlement::sum('net_amount');
        $thisMonth = Settlement::whereMonth('settlement_date', now()->month)->sum('net_amount');
        $pendingPayment = Settlement::where('status', 'PENDING')->sum('net_amount');
        $averageWeekly = Settlement::where('settlement_date', '>=', now()->subWeeks(5))->avg('net_amount');

        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-user-group',
            'title' => 'Settlements',
            'subtitle' => 'Track driver pay periods and settlement history.',
            'stats' => [
                [
                    'label' => 'Total Earned (YTD)',
                    'value' => $currency . ' ' . number_format($totalEarned, 2),
                    'icon' => 'heroicon-o-banknotes',
                    'sub' => Settlement::count() . ' settlements total',
                ],
                [
                    'label' => 'This Month',
                    'value' => $currency . ' ' . number_format($thisMonth, 2),
                    'icon' => 'heroicon-o-calendar',
                    'color' => 'info',
                    'sub' => Settlement::whereMonth('settlement_date', now()->month)->count() . ' settlements processed',
                ],
                [
                    'label' => 'Pending Payment',
                    'value' => $currency . ' ' . number_format($pendingPayment, 2),
                    'icon' => 'heroicon-o-clock',
                    'color' => 'warning',
                    'sub' => 'All pending payments',
                ],
                [
                    'label' => 'Average Weekly',
                    'value' => $currency . ' ' . number_format($averageWeekly ?? 0, 2),
                    'icon' => 'heroicon-o-chart-bar',
                    'color' => 'success',
                    'sub' => 'Based on last 5 weeks',
                ],
            ],
        ]);
    }

    // Custom methods for table row actions
    public function viewSettlement(string $recordId): void
    {
        // Redirect to the edit page for the settlement record
        $this->redirect(SettlementResource::getUrl('edit', ['record' => $recordId]));

        Notification::make()
            ->title('Viewing Settlement '.$recordId)
            ->success()
            ->send();
    }

    public function downloadSettlement(string $recordId): void
    {
        // Implement actual download logic here. For now, a placeholder notification.
        Notification::make()
            ->title('Download initiated for Settlement '.$recordId)
            ->success()
            ->send();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SettlementFilterWidget::class,
        ];
    }
}
