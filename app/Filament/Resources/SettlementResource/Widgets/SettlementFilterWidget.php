<?php

namespace App\Filament\Resources\SettlementResource\Widgets;

use App\Models\Truck;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget; // For dynamic truck options
use Illuminate\Http\RedirectResponse; // Added

class SettlementFilterWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.settlement-filter-widget';

    public ?array $data = []; // Holds the form data

    public function mount(): void
    {
        // Initialize form data from session or default values
        $this->form->fill([
            'date_from' => request('date_from'), // Use request to persist filters
            'date_to' => request('date_to'),
            'settlement_number' => request('settlement_number', ''),
            'property' => request('property', ''),
            'status' => request('status', ''),
            'quick_filter' => request('quick_filter', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('date_from')->label('Date From'),
                DatePicker::make('date_to')->label('Date To'),
                TextInput::make('settlement_number')->label('Settlement Number')->placeholder('Search settlement #...'),
                Select::make('property')->label('Truck')->options(
                    Truck::pluck('plate_number', 'id')->prepend('All Trucks', '')->toArray() // Dynamic trucks
                ),
                Select::make('status')->label('Status')->options([
                    '' => 'All Statuses',
                    'PENDING' => 'Pending',
                    'PAID' => 'Paid',
                    'PROCESSING' => 'Processing',
                ]),
                Select::make('quick_filter')->label('Quick Filter')->options([
                    '' => 'Custom Range',
                    'today' => 'Today',
                    'week' => 'This Week',
                    'month' => 'This Month',
                    'quarter' => 'This Quarter',
                ]),
                Actions::make([
                    Action::make('search')
                        ->label('Search')
                        ->action('applyFilters') // Livewire action method
                        ->button()
                        ->color('primary')
                        ->icon('heroicon-m-magnifying-glass'), // Filament button icon
                    Action::make('reset')
                        ->label('Reset Filters')
                        ->action('resetFilters') // Livewire action method
                        ->outlined()
                        ->icon('heroicon-m-arrow-path'), // Filament button icon
                ])->extraAttributes(['class' => 'col-span-full flex justify-end gap-3 pt-4']), // Align buttons right
            ])
            ->statePath('data') // Crucial for form data binding
            ->columns(6); // Matches original layout
    }

    public function applyFilters(): void
    {
        // Emit Livewire event to ListSettlements page to apply filters
        $this->dispatch('applySettlementFilters', $this->data);
    }

    public function resetFilters(): void
    {
        $this->form->fill([
            'date_from' => null,
            'date_to' => null,
            'settlement_number' => '',
            'property' => '',
            'status' => '',
            'quick_filter' => '',
        ]);
        $this->dispatch('applySettlementFilters', $this->data);
    }

    public function export(string $type)
    {
        return redirect()->route('settlements.export', array_merge(['type' => $type], $this->data));
    }
}
