<?php

namespace App\Filament\Resources\SettlementResource\Pages;

use App\Filament\Resources\SettlementResource;
use App\Models\Settlement;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On; // Added for notifications

class ListSettlements extends ListRecords
{
    protected static string $resource = SettlementResource::class;

    protected static string $view = 'filament.resources.settlement-resource.pages.list-settlements'; // Custom view





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

    // getHeaderWidgets() method is removed as widgets are rendered directly in the custom view
}
