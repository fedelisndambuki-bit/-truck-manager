<?php

namespace App\Filament\Clusters\SmartWeighbridge\Pages;

use App\Filament\Clusters\SmartWeighbridge;
use App\Models\DispatchOrder;
use App\Services\Quarry\WeighbridgeTicketPdfService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * The ticket-generation hub — select a Dispatch Order, then generate any of
 * the 7 branded single-record documents via WeighbridgeTicketPdfService.
 */
class Tickets extends Page implements HasForms
{
    use HasModuleAccess;
    use InteractsWithForms;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'weighbridge.export';
    }

    protected static ?string $cluster = SmartWeighbridge::class;

    protected static ?string $navigationIcon = 'heroicon-o-printer';

    protected static ?string $navigationLabel = 'Tickets';

    protected static ?int $navigationSort = 15;

    protected static ?string $title = 'Tickets';

    protected static string $view = 'filament.clusters.smart-weighbridge.pages.tickets';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('dispatch_order_id')
                    ->label('Dispatch Order')
                    ->options(fn () => DispatchOrder::query()
                        ->when(Auth::user()?->company_id, fn ($q) => $q->where('company_id', Auth::user()->company_id))
                        ->latest('id')->limit(100)->get()
                        ->mapWithKeys(fn (DispatchOrder $o) => [$o->id => $o->dispatch_number . ' — ' . $o->vehicle_registration]))
                    ->searchable()
                    ->required()
                    ->live(),
            ])
            ->statePath('data');
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659" />',
            'title' => 'Tickets',
            'subtitle' => 'Generate the branded weighbridge, loading, gross, dispatch, delivery note, gate pass and certificate of weight documents.',
        ]);
    }

    protected function selectedOrder(): ?DispatchOrder
    {
        $id = $this->data['dispatch_order_id'] ?? null;

        return $id ? DispatchOrder::find($id) : null;
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Weighbridge Ticket (Tare)', 'description' => 'Tare weight capture record.', 'icon' => 'heroicon-o-scale', 'action' => 'exportTareTicket'],
            ['label' => 'Digital Loading Ticket', 'description' => 'The QR-coded loading instruction.', 'icon' => 'heroicon-o-qr-code', 'action' => 'exportLoadingTicket'],
            ['label' => 'Weighbridge Ticket (Gross)', 'description' => 'Gross weight, net weight and variance.', 'icon' => 'heroicon-o-scale', 'action' => 'exportGrossTicket'],
            ['label' => 'Dispatch Ticket', 'description' => 'The full dispatch summary.', 'icon' => 'heroicon-o-document-text', 'action' => 'exportDispatchTicket'],
            ['label' => 'Delivery Note', 'description' => 'Proof of delivery confirmation.', 'icon' => 'heroicon-o-truck', 'action' => 'exportDeliveryNote'],
            ['label' => 'Gate Pass', 'description' => 'Gate in/out and security clearance.', 'icon' => 'heroicon-o-identification', 'action' => 'exportGatePass'],
            ['label' => 'Certificate of Weight', 'description' => 'Certified weighbridge reading.', 'icon' => 'heroicon-o-document-check', 'action' => 'exportCertificateOfWeight'],
        ];
    }

    protected function guardOrder(): ?DispatchOrder
    {
        $order = $this->selectedOrder();

        if (! $order) {
            Notification::make()->title('Select a Dispatch Order first')->warning()->send();
        }

        return $order;
    }

    public function exportTareTicket()
    {
        if (! $order = $this->guardOrder()) {
            return null;
        }

        return app(WeighbridgeTicketPdfService::class)->tareTicket($order);
    }

    public function exportLoadingTicket()
    {
        if (! $order = $this->guardOrder()) {
            return null;
        }

        return app(WeighbridgeTicketPdfService::class)->loadingTicket($order);
    }

    public function exportGrossTicket()
    {
        if (! $order = $this->guardOrder()) {
            return null;
        }

        return app(WeighbridgeTicketPdfService::class)->grossTicket($order);
    }

    public function exportDispatchTicket()
    {
        if (! $order = $this->guardOrder()) {
            return null;
        }

        return app(WeighbridgeTicketPdfService::class)->dispatchTicket($order);
    }

    public function exportDeliveryNote()
    {
        if (! $order = $this->guardOrder()) {
            return null;
        }

        return app(WeighbridgeTicketPdfService::class)->deliveryNote($order);
    }

    public function exportGatePass()
    {
        if (! $order = $this->guardOrder()) {
            return null;
        }

        return app(WeighbridgeTicketPdfService::class)->gatePass($order);
    }

    public function exportCertificateOfWeight()
    {
        if (! $order = $this->guardOrder()) {
            return null;
        }

        return app(WeighbridgeTicketPdfService::class)->certificateOfWeight($order);
    }
}
