<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockpileResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockpileResource;
use App\Models\PlantQualityTest;
use App\Models\ProductionStockTransfer;
use App\Models\StockMovement;
use App\Models\Stockpile;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStockpile extends ViewRecord
{
    protected static string $resource = StockpileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Stockpile')->tabs([
                Tab::make('Overview')->schema([
                    Section::make()->schema([
                        TextEntry::make('stockpile_code')->label('Code'),
                        TextEntry::make('label')->label('Name')->placeholder('—'),
                        TextEntry::make('material.name')->label('Material'),
                        TextEntry::make('extractionSite.name')->label('Quarry'),
                        TextEntry::make('crushingPlant.name')->label('Crushing Plant')->placeholder('—'),
                        TextEntry::make('stockyard')->label('Stockyard / Depot')->placeholder('—'),
                        TextEntry::make('location_type')->label('Location Type'),
                        TextEntry::make('gps_lat')->label('GPS Latitude')->placeholder('—'),
                        TextEntry::make('gps_lng')->label('GPS Longitude')->placeholder('—'),
                        TextEntry::make('max_level')->label('Maximum Capacity')->suffix(' t')->placeholder('—'),
                        TextEntry::make('quantity_available')->label('Current Quantity')->suffix(' t'),
                        TextEntry::make('available_capacity')->label('Available Capacity')
                            ->state(fn (Stockpile $record) => $record->availableCapacity() !== null ? $record->availableCapacity() . ' t' : '—'),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => StockpileResource::getStatusColor($state)),
                        TextEntry::make('quality_status')->badge()->color(fn (string $state): string => StockpileResource::getQualityStatusColor($state)),
                    ])->columns(4),
                ]),

                Tab::make('Inventory')->schema([
                    Section::make()->schema([
                        TextEntry::make('quantity_available')->label('Current Quantity')->suffix(' t'),
                        TextEntry::make('quantity_reserved')->label('Reserved Quantity')->suffix(' t'),
                        TextEntry::make('available_for_sale')->label('Available Quantity')
                            ->state(fn (Stockpile $record) => $record->availableForSale() . ' t'),
                        TextEntry::make('current_value')->label('Current Value')
                            ->state(fn (Stockpile $record) => session('currency', 'KES') . ' ' . number_format($record->value(), 2)),
                        TextEntry::make('last_movement')->label('Last Movement')
                            ->state(function (Stockpile $record) {
                                $last = StockMovement::query()
                                    ->where('source_stockpile_id', $record->id)
                                    ->orWhere('destination_stockpile_id', $record->id)
                                    ->latest('date')->first();

                                return $last ? $last->movement_type . ' — ' . $last->date->format('d M Y') : '—';
                            }),
                    ])->columns(3),
                ]),

                Tab::make('Movements')->schema([
                    RepeatableEntry::make('movementHistory')
                        ->label('Recent Movements')
                        ->getStateUsing(fn (Stockpile $record) => StockMovement::query()
                            ->where('source_stockpile_id', $record->id)
                            ->orWhere('destination_stockpile_id', $record->id)
                            ->latest('date')->limit(15)->get())
                        ->schema([
                            TextEntry::make('movement_number')->label('Movement #'),
                            TextEntry::make('movement_type')->badge(),
                            TextEntry::make('date')->date(),
                            TextEntry::make('quantity')->suffix(' t'),
                        ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Reservations')->schema([
                    RepeatableEntry::make('reservations')->label('Reservations')->schema([
                        TextEntry::make('reservation_number')->label('Reservation #'),
                        TextEntry::make('customer.company_name')->label('Customer')->placeholder('—'),
                        TextEntry::make('quantity_reserved')->suffix(' t'),
                        TextEntry::make('status')->badge(),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Transfers')->schema([
                    RepeatableEntry::make('relatedTransfers')
                        ->label('Related Stock Transfers')
                        ->getStateUsing(fn (Stockpile $record) => ProductionStockTransfer::query()
                            ->where('source_stockpile_id', $record->id)
                            ->orWhere('destination_stockpile_id', $record->id)
                            ->latest('created_at')->limit(15)->get())
                        ->schema([
                            TextEntry::make('transfer_number')->label('Transfer #'),
                            TextEntry::make('quantity')->suffix(' t'),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('received_status')->label('Received'),
                        ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Quality')->schema([
                    Section::make()->schema([
                        TextEntry::make('quality_status')->badge()->color(fn (string $state): string => StockpileResource::getQualityStatusColor($state)),
                    ]),
                    RepeatableEntry::make('materialQualityTests')
                        ->label('Related Lab Tests (Material)')
                        ->getStateUsing(fn (Stockpile $record) => PlantQualityTest::query()
                            ->where('material_id', $record->material_id)
                            ->latest('created_at')->limit(10)->get())
                        ->schema([
                            TextEntry::make('sample_number'),
                            TextEntry::make('pass_fail')->label('Result')->formatStateUsing(fn ($state) => $state ? 'Pass' : 'Fail')->badge()
                                ->color(fn ($state) => $state ? 'success' : 'danger'),
                            TextEntry::make('approved_for_dispatch')->label('Approved for Dispatch')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                        ])->columns(3)->columnSpanFull(),
                ]),

                Tab::make('Documents')->schema([
                    RepeatableEntry::make('documents')->hiddenLabel()->schema([
                        TextEntry::make('category')->badge(),
                        TextEntry::make('file_path')->label('File')->formatStateUsing(fn ($state) => $state ? basename($state) : '—'),
                    ])->columns(2)->columnSpanFull(),
                ]),

                Tab::make('Analytics')->schema([
                    Section::make()->schema([
                        TextEntry::make('turnover_contribution')
                            ->label('Dispatched (30 Days)')
                            ->state(function (Stockpile $record) {
                                $dispatched = StockMovement::query()
                                    ->where('source_stockpile_id', $record->id)
                                    ->where('movement_type', 'Dispatch')
                                    ->where('date', '>=', now()->subDays(30))
                                    ->sum('quantity');

                                return number_format((float) $dispatched, 1) . ' t';
                            }),
                        TextEntry::make('utilization_percent')
                            ->label('Capacity Utilization')
                            ->state(function (Stockpile $record) {
                                if (! $record->max_level) {
                                    return '—';
                                }

                                return round(((float) $record->quantity_available / (float) $record->max_level) * 100, 1) . '%';
                            }),
                        TextEntry::make('reservation_ratio')
                            ->label('Reserved / Available Ratio')
                            ->state(function (Stockpile $record) {
                                if ((float) $record->quantity_available <= 0) {
                                    return '—';
                                }

                                return round(((float) $record->quantity_reserved / (float) $record->quantity_available) * 100, 1) . '%';
                            }),
                    ])->columns(3),
                ]),

                Tab::make('History')->schema([
                    Section::make()->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->label('Last Updated')->dateTime(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => StockpileResource::getStatusColor($state)),
                    ])->columns(3),
                ]),
            ])->columnSpanFull(),
        ]);
    }
}
