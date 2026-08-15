<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\CustomerOrderResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\CustomerOrderResource;
use App\Models\CustomerOrder;
use App\Models\DispatchOrder;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerOrder extends ViewRecord
{
    protected static string $resource = CustomerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function latestDispatchOrder(CustomerOrder $record): ?DispatchOrder
    {
        return $record->dispatchOrders()->latest('id')->first();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Customer Order')->tabs([
                Tab::make('Overview')->schema([
                    Section::make()->schema([
                        TextEntry::make('order_number')->label('Order #'),
                        TextEntry::make('customer.company_name')->label('Customer')->placeholder('—'),
                        TextEntry::make('material.name')->label('Product')->placeholder('—'),
                        TextEntry::make('quantity')->suffix(' t'),
                        TextEntry::make('priority')->badge(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => CustomerOrderResource::getStatusColor($state)),
                        TextEntry::make('requested_delivery_date')->label('Requested Delivery')->date('d M Y')->placeholder('—'),
                        TextEntry::make('plannedBy.full_name')->label('Planned By')->placeholder('—'),
                    ])->columns(4),
                ]),

                Tab::make('Order Details')->schema([
                    Section::make()->schema([
                        TextEntry::make('customer_site')->label('Delivery Site / Address')->placeholder('—')->columnSpan(2),
                        TextEntry::make('sales_contract_number')->label('Sales Contract #')->placeholder('—'),
                        TextEntry::make('quotation_number')->label('Quotation #')->placeholder('—'),
                        TextEntry::make('product_grade')->label('Product Grade')->placeholder('—'),
                        TextEntry::make('unit_price')->label('Unit Price')->money('KES')->placeholder('—'),
                        TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ])->columns(3),
                ]),

                Tab::make('Reservation')->schema([
                    Section::make()->schema([
                        TextEntry::make('reservation.reservation_number')->label('Reservation #')->placeholder('Not reserved'),
                        TextEntry::make('reservation.quantity_reserved')->label('Quantity Reserved')->suffix(' t')->placeholder('—'),
                        TextEntry::make('reservation.stockpile.label')->label('Stockpile')->placeholder('—'),
                        TextEntry::make('reservation.status')->label('Reservation Status')->badge()->placeholder('—'),
                        TextEntry::make('reservation.reservation_date')->label('Reserved On')->dateTime()->placeholder('—'),
                    ])->columns(3),
                ]),

                Tab::make('Dispatch Orders')->schema([
                    RepeatableEntry::make('dispatchOrders')->hiddenLabel()->schema([
                        TextEntry::make('dispatch_number')->label('Dispatch #'),
                        TextEntry::make('vehicle_registration')->label('Vehicle')->placeholder('Unassigned'),
                        TextEntry::make('net_weight')->label('Net Weight')->suffix(' t')->placeholder('—'),
                        TextEntry::make('status')->badge(),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Truck & Driver')->schema([
                    Section::make()->schema([
                        TextEntry::make('truck_vehicle')->label('Vehicle')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->vehicle_registration),
                        TextEntry::make('truck_driver')->label('Driver')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->driver_name),
                        TextEntry::make('truck_driver_phone')->label('Driver Phone')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->driver_phone),
                        TextEntry::make('truck_driver_license')->label('Driving Licence')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->driver_license_number),
                    ])->columns(4),
                ]),

                Tab::make('Weighbridge')->schema([
                    Section::make()->schema([
                        TextEntry::make('wb_tare')->label('Tare Weight')->suffix(' t')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->tare_weight),
                        TextEntry::make('wb_gross')->label('Gross Weight')->suffix(' t')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->gross_weight),
                        TextEntry::make('wb_net')->label('Net Weight')->suffix(' t')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->net_weight),
                        TextEntry::make('wb_variance')->label('Variance')->suffix(' t')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->variance),
                        TextEntry::make('wb_status')->label('Weight Status')->badge()->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->weight_status),
                    ])->columns(3),
                ]),

                Tab::make('Delivery')->schema([
                    Section::make()->schema([
                        TextEntry::make('delivery_eta')->label('ETA')->dateTime()->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->eta),
                        TextEntry::make('delivery_distance')->label('Distance Remaining')->suffix(' km')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->distance_remaining_km),
                        TextEntry::make('delivery_arrived')->label('Arrived At')->dateTime()->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->arrived_at),
                        TextEntry::make('delivery_status')->label('Status')->badge()->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->status),
                        TextEntry::make('delivery_notes')->label('Delivery Notes')->placeholder('—')->columnSpanFull()
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->delivery_notes),
                    ])->columns(4),
                ]),

                Tab::make('Proof of Delivery')->schema([
                    Section::make()->schema([
                        TextEntry::make('pod_delivered')->label('Delivered Quantity')->suffix(' t')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->proofOfDelivery?->delivered_quantity),
                        TextEntry::make('pod_rejected')->label('Rejected Quantity')->suffix(' t')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->proofOfDelivery?->rejected_quantity),
                        TextEntry::make('pod_receiver')->label('Received By')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->proofOfDelivery?->receiver_name),
                        TextEntry::make('pod_time')->label('Delivery Time')->dateTime()->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->proofOfDelivery?->delivery_time),
                        TextEntry::make('pod_status')->label('Status')->badge()->placeholder('—')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->proofOfDelivery?->status),
                    ])->columns(3),
                ]),

                Tab::make('Timeline')->schema([
                    Section::make()->schema([
                        TextEntry::make('created_at')->label('Order Created')->dateTime(),
                        TextEntry::make('requested_delivery_date')->label('Requested Delivery')->date('d M Y')->placeholder('—'),
                        TextEntry::make('reservation.reservation_date')->label('Stock Reserved')->dateTime()->placeholder('Pending'),
                        TextEntry::make('dispatch_created')->label('Dispatch Order Created')->dateTime()->placeholder('Pending')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->created_at),
                        TextEntry::make('dispatch_exit')->label('Truck Departed')->dateTime()->placeholder('Pending')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->exit_at),
                        TextEntry::make('dispatch_arrived')->label('Delivered')->dateTime()->placeholder('Pending')
                            ->state(fn (CustomerOrder $record) => $this->latestDispatchOrder($record)?->arrived_at),
                        TextEntry::make('updated_at')->label('Last Updated')->dateTime(),
                    ])->columns(3),
                ]),
            ])->columnSpanFull(),
        ]);
    }
}
