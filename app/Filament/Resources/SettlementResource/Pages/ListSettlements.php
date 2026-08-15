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
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0116.5 0M4.5 12a7.5 7.5 0 0115 0m-15 0a7.5 7.5 0 1015 0m-15 0h15M5.106 5.79a60.07 60.07 0 0113.788 0" />',
            'title' => 'Settlements',
            'subtitle' => 'Track driver pay periods and settlement history.',
            'stats' => [
                [
                    'label' => 'Total Earned (YTD)',
                    'value' => $currency . ' ' . number_format($totalEarned, 2),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    'sub' => Settlement::count() . ' settlements total',
                ],
                [
                    'label' => 'This Month',
                    'value' => $currency . ' ' . number_format($thisMonth, 2),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />',
                    'color' => 'info',
                    'sub' => Settlement::whereMonth('settlement_date', now()->month)->count() . ' settlements processed',
                ],
                [
                    'label' => 'Pending Payment',
                    'value' => $currency . ' ' . number_format($pendingPayment, 2),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                    'color' => 'warning',
                    'sub' => 'All pending payments',
                ],
                [
                    'label' => 'Average Weekly',
                    'value' => $currency . ' ' . number_format($averageWeekly ?? 0, 2),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.518l2.74-1.22m0 0l-5.94-2.281m5.94 2.28l-2.28 5.941" />',
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
