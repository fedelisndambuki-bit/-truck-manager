<?php

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\Models\Truck;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form; // Added
use Filament\Widgets\Widget;
use App\Models\Invoice; // Added for options

class InvoiceFilterWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.resources.invoice-resource.widgets.invoice-filter-widget';

    public ?array $data = []; // Holds the form data

    public function mount(): void
    {
        // Initialize form data from session or default values
        $this->form->fill([
            'date_from' => request('date_from'), // Use request to persist filters
            'date_to' => request('date_to'),
            'invoice_number' => request('invoice_number', ''),
            'property' => request('property', ''), // For truck filtering
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
                TextInput::make('invoice_number')->label('Invoice Number')->placeholder('Search invoice #...'),
                Select::make('property')->label('Truck')->options(
                    Truck::pluck('plate_number', 'id')->prepend('All Trucks', '')->toArray() // Dynamic trucks
                ),
                Select::make('status')->label('Status')->options([
                    '' => 'All Statuses',
                    'draft' => 'Draft',
                    'sent' => 'Sent',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
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
        // Emit Livewire event to ListInvoices page to apply filters
        $this->dispatch('applyInvoiceFilters', $this->form->getState());
    }

    public function resetFilters(): void
    {
        $this->form->fill([
            'date_from' => null,
            'date_to' => null,
            'invoice_number' => '',
            'property' => '',
            'status' => '',
            'quick_filter' => '',
        ]);
        $this->dispatch('applyInvoiceFilters', $this->form->getState());
    }

    public function export(string $type)
    {
        // Get current filter state to pass to export route
        $filters = $this->form->getState();

        // Redirect to the export route for invoices
        return redirect()->route('invoices.export', ['type' => $type, ...$filters]);
    }
}
