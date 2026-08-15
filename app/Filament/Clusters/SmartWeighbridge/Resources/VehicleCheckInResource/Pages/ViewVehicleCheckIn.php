<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\VehicleCheckInResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleCheckInResource;
use App\Models\DispatchOrder;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewVehicleCheckIn extends ViewRecord
{
    protected static string $resource = VehicleCheckInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Dispatch Order')->tabs([
                Tab::make('Overview')->schema([
                    Section::make()->schema([
                        TextEntry::make('dispatch_number')->label('Dispatch #'),
                        TextEntry::make('order_number')->label('Order #')->placeholder('—'),
                        TextEntry::make('vehicle_registration')->label('Vehicle'),
                        TextEntry::make('trailer_number')->label('Trailer')->placeholder('—'),
                        TextEntry::make('customer.company_name')->label('Customer')->placeholder('—'),
                        TextEntry::make('material.name')->label('Product')->placeholder('—'),
                        TextEntry::make('product_grade')->label('Product Grade')->placeholder('—'),
                        TextEntry::make('stockpile.label')->label('Assigned Stockpile')->placeholder('—'),
                        TextEntry::make('net_weight')->label('Net Weight')->suffix(' t')->placeholder('—'),
                        TextEntry::make('priority')->badge(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => VehicleCheckInResource::getStatusColor($state)),
                        TextEntry::make('invoice.invoice_number')->label('Invoice')->placeholder('—'),
                    ])->columns(4),
                ]),

                Tab::make('Check-In')->schema([
                    Section::make('Driver & Transport')->schema([
                        TextEntry::make('driver_name')->label('Driver')->placeholder('—'),
                        TextEntry::make('driver_national_id')->label('National ID')->placeholder('—'),
                        TextEntry::make('driver_license_number')->label('Driving Licence')->placeholder('—'),
                        TextEntry::make('driver_license_expiry')->label('Licence Expiry')->date()->placeholder('—'),
                        TextEntry::make('driver_phone')->label('Phone')->placeholder('—'),
                        TextEntry::make('transportCompany.name')->label('Transport Company')->placeholder('—'),
                    ])->columns(3),
                    Section::make('Gate & Security')->schema([
                        TextEntry::make('gate_in_at')->label('Arrival Time')->dateTime()->placeholder('—'),
                        TextEntry::make('gate')->label('Gate')->placeholder('—'),
                        TextEntry::make('securityOfficer.full_name')->label('Security Officer')->placeholder('—'),
                        TextEntry::make('gate_gps_lat')->label('GPS Latitude')->placeholder('—'),
                        TextEntry::make('gate_gps_lng')->label('GPS Longitude')->placeholder('—'),
                        TextEntry::make('vehicle_inspection_passed')->label('Inspection Passed')->formatStateUsing(fn ($state) => $state === null ? '—' : ($state ? 'Yes' : 'No')),
                        TextEntry::make('vehicle_inspection_notes')->label('Inspection Notes')->placeholder('—')->columnSpanFull(),
                    ])->columns(3),
                ]),

                Tab::make('Weighing')->schema([
                    Section::make('Tare')->schema([
                        TextEntry::make('tare_weight')->suffix(' t')->placeholder('—'),
                        TextEntry::make('tare_weighed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('tareOperator.full_name')->label('Operator')->placeholder('—'),
                        TextEntry::make('tare_locked')->label('Locked')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                    ])->columns(4),
                    Section::make('Gross')->schema([
                        TextEntry::make('gross_weight')->suffix(' t')->placeholder('—'),
                        TextEntry::make('gross_weighed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('grossOperator.full_name')->label('Operator')->placeholder('—'),
                        TextEntry::make('reweigh_count')->label('Reweighs'),
                    ])->columns(4),
                    RepeatableEntry::make('weighingLogs')->label('Weighing Audit Trail')->schema([
                        TextEntry::make('sequence'),
                        TextEntry::make('weighing_type')->badge(),
                        TextEntry::make('weight')->suffix(' t'),
                        TextEntry::make('previous_weight')->label('Previous Weight')->suffix(' t')->placeholder('—'),
                        TextEntry::make('recorded_at')->dateTime(),
                    ])->columns(5)->columnSpanFull(),
                ]),

                Tab::make('Loading')->schema([
                    RepeatableEntry::make('loadingInstructions')->label('Loading Instructions')->schema([
                        TextEntry::make('loading_number')->label('Loading #'),
                        TextEntry::make('loadingBay.name')->label('Bay')->placeholder('—'),
                        TextEntry::make('loaderOperator.full_name')->label('Loader Operator')->placeholder('—'),
                        TextEntry::make('estimated_quantity_loaded')->label('Est. Quantity')->suffix(' t')->placeholder('—'),
                        TextEntry::make('status')->badge(),
                    ])->columns(5)->columnSpanFull(),
                    Section::make()->schema([
                        TextEntry::make('loading_started_at')->dateTime()->placeholder('—'),
                        TextEntry::make('loading_completed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('loading_duration')
                            ->label('Duration')
                            ->state(fn (DispatchOrder $record) => $record->loadingDurationMinutes() !== null ? $record->loadingDurationMinutes() . ' min' : '—'),
                    ])->columns(3),
                ]),

                Tab::make('Validation')->schema([
                    Section::make()->schema([
                        TextEntry::make('target_net_weight')->label('Target')->suffix(' t')->placeholder('—'),
                        TextEntry::make('net_weight')->label('Actual')->suffix(' t')->placeholder('—'),
                        TextEntry::make('variance')->label('Difference')->suffix(' t')->placeholder('—'),
                        TextEntry::make('variance_percent')->label('Variance %')->suffix('%')->placeholder('—'),
                        TextEntry::make('tolerance_tonnes')->label('Tolerance')->suffix(' t'),
                        TextEntry::make('weight_status')->label('Status')->badge()->placeholder('—'),
                    ])->columns(3),
                ]),

                Tab::make('Approval')->schema([
                    RepeatableEntry::make('approvalRequests')->label('Approval History')->schema([
                        TextEntry::make('reference_number'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('submitted_at')->dateTime()->placeholder('—'),
                        TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Exit & Delivery')->schema([
                    Section::make('Exit')->schema([
                        TextEntry::make('exit_at')->dateTime()->placeholder('—'),
                        TextEntry::make('exit_gate')->placeholder('—'),
                        TextEntry::make('securityClearanceBy.full_name')->label('Security Clearance')->placeholder('—'),
                        TextEntry::make('security_clearance_at')->dateTime()->placeholder('—'),
                    ])->columns(4),
                    Section::make('Proof of Delivery')->schema([
                        TextEntry::make('proofOfDelivery.delivered_quantity')->label('Delivered Quantity')->suffix(' t')->placeholder('—'),
                        TextEntry::make('proofOfDelivery.receiver_name')->label('Receiver')->placeholder('—'),
                        TextEntry::make('proofOfDelivery.delivery_time')->label('Delivery Time')->dateTime()->placeholder('—'),
                        TextEntry::make('proofOfDelivery.status')->label('Status')->badge()->placeholder('—'),
                    ])->columns(4),
                ]),

                Tab::make('Documents')->schema([
                    RepeatableEntry::make('documents')->hiddenLabel()->schema([
                        TextEntry::make('category')->badge(),
                        TextEntry::make('file_path')->label('File')->formatStateUsing(fn ($state) => $state ? basename($state) : '—'),
                    ])->columns(2)->columnSpanFull(),
                ]),

                Tab::make('Timeline')->schema([
                    Section::make()->schema([
                        TextEntry::make('created_at')->label('Order Created')->dateTime()->placeholder('—'),
                        TextEntry::make('gate_in_at')->label('Check-In')->dateTime()->placeholder('Pending'),
                        TextEntry::make('tare_weighed_at')->label('Tare Weight')->dateTime()->placeholder('Pending'),
                        TextEntry::make('loading_instruction_generated_at')->label('Loading Assigned')->dateTime()->placeholder('Pending'),
                        TextEntry::make('loading_started_at')->label('Loading Started')->dateTime()->placeholder('Pending'),
                        TextEntry::make('loading_completed_at')->label('Loading Completed')->dateTime()->placeholder('Pending'),
                        TextEntry::make('gross_weighed_at')->label('Gross Weight')->dateTime()->placeholder('Pending'),
                        TextEntry::make('security_clearance_at')->label('Security Clearance')->dateTime()->placeholder('Pending'),
                        TextEntry::make('exit_at')->label('Exit')->dateTime()->placeholder('Pending'),
                    ])->columns(3),
                ]),

                Tab::make('History')->schema([
                    Section::make()->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->label('Last Updated')->dateTime(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => VehicleCheckInResource::getStatusColor($state)),
                    ])->columns(3),
                    RepeatableEntry::make('exceptions')->label('Exceptions')->schema([
                        TextEntry::make('exception_number')->label('Exception #'),
                        TextEntry::make('exception_type')->badge(),
                        TextEntry::make('status')->badge(),
                    ])->columns(3)->columnSpanFull(),
                ]),
            ])->columnSpanFull(),
        ]);
    }
}
