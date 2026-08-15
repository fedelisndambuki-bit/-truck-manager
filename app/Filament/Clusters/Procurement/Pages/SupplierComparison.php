<?php

namespace App\Filament\Clusters\Procurement\Pages;

use App\Filament\Clusters\Procurement;
use App\Models\RequestForQuotation;
use App\Services\SupplierComparisonService;
use App\Traits\HasModuleAccess;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SupplierComparison extends Page implements HasForms
{
    use HasModuleAccess;
    use InteractsWithForms;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $cluster = Procurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Supplier Comparison';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Supplier Comparison';

    protected static string $view = 'filament.clusters.procurement.pages.supplier-comparison';

    public ?array $data = [];

    public ?int $rfq_id = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z" />',
            'title' => 'Supplier Comparison',
            'subtitle' => 'Side-by-side quotation comparison — price, lead time, warranty, rating and a recommended supplier.',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('rfq_id')
                    ->label('RFQ')
                    ->options(fn () => RequestForQuotation::query()
                        ->when(Auth::user()?->company_id, fn ($q, $id) => $q->where('company_id', $id))
                        ->whereIn('status', ['Open', 'Closed', 'Awarded'])
                        ->pluck('rfq_number', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn ($state) => $this->rfq_id = $state),
            ])
            ->statePath('data');
    }

    public function getComparisonRows(): Collection
    {
        if (! $this->rfq_id) {
            return collect();
        }

        $rfq = RequestForQuotation::find($this->rfq_id);

        if (! $rfq) {
            return collect();
        }

        return app(SupplierComparisonService::class)->compare($rfq);
    }

    public function awardQuotation(int $quotationId): void
    {
        $rfq = RequestForQuotation::find($this->rfq_id);
        $quotation = \App\Models\SupplierQuotation::find($quotationId);

        if (! $rfq || ! $quotation) {
            return;
        }

        $order = $rfq->awardTo($quotation);

        \Filament\Notifications\Notification::make()
            ->title("Awarded to {$quotation->supplier?->company_name} — Purchase Order {$order->po_number} created")
            ->success()
            ->send();
    }
}
