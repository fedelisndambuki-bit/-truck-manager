<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\ProductionRecordResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\ProductionRecordResource;
use App\Models\FuelLog;
use App\Models\Maintenance;
use App\Models\ProductionLog;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProductionRecord extends ViewRecord
{
    protected static string $resource = ProductionRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Production Record')->tabs([
                Tab::make('Overview')->schema([
                    Section::make()->schema([
                        TextEntry::make('production_number')->label('Production #'),
                        TextEntry::make('extractionSite.name')->label('Quarry'),
                        TextEntry::make('date')->date(),
                        TextEntry::make('shift')->badge(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => ProductionRecordResource::getStatusColor($state)),
                        TextEntry::make('material_type')->badge(),
                        TextEntry::make('quantity_tonnes')->label('Raw Material Extracted')->suffix(' t'),
                        TextEntry::make('quarryFace.name')->label('Quarry Face')->placeholder('—'),
                        TextEntry::make('pit.name')->label('Pit')->placeholder('—'),
                    ])->columns(3),
                ]),

                Tab::make('Production')->schema([
                    Section::make()->schema([
                        TextEntry::make('feed_quantity')->label('Feed Quantity')->suffix(' t')->placeholder('—'),
                        TextEntry::make('crusher_throughput')->label('Crusher Throughput')->suffix(' t')->placeholder('—'),
                        TextEntry::make('finished_product_quantity')->label('Finished Product Quantity')->suffix(' t')->placeholder('—'),
                        TextEntry::make('rejected_material')->label('Rejected Material')->suffix(' t')->placeholder('—'),
                        TextEntry::make('waste_material')->label('Waste Material')->suffix(' t')->placeholder('—'),
                        TextEntry::make('by_products')->label('By-products')->suffix(' t')->placeholder('—'),
                        TextEntry::make('recovery_percent')->label('Recovery %')->suffix('%')->placeholder('—'),
                        TextEntry::make('yield_percent')->label('Yield %')->suffix('%')->placeholder('—'),
                        TextEntry::make('remarks')->columnSpanFull()->placeholder('—'),
                    ])->columns(4),
                ]),

                Tab::make('Products')->schema([
                    RepeatableEntry::make('outputs')->label('Product Output')->schema([
                        TextEntry::make('material.name')->label('Product'),
                        TextEntry::make('quantity_produced')->suffix(fn ($record) => ' ' . ($record->unit ?? 'tonne')),
                        TextEntry::make('stockpile.label')->label('Destination Stockpile')->placeholder('—'),
                        TextEntry::make('quality_grade')->placeholder('—'),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Equipment')->schema([
                    Section::make()->schema([
                        TextEntry::make('crushingPlant.name')->label('Crushing Plant')->placeholder('—'),
                        TextEntry::make('crushingPlant.status')->label('Plant Status')->badge()->placeholder('—'),
                        TextEntry::make('productionLine.name')->label('Production Line')->placeholder('—'),
                    ])->columns(3),
                ]),

                Tab::make('Operators')->schema([
                    Section::make()->schema([
                        TextEntry::make('supervisor.full_name')->label('Supervisor')->placeholder('—'),
                        TextEntry::make('operator.full_name')->label('Operator')->placeholder('—'),
                        TextEntry::make('loggedBy.full_name')->label('Logged By')->placeholder('—'),
                    ])->columns(3),
                ]),

                Tab::make('Downtime')->schema([
                    RepeatableEntry::make('downtimes')->label('Downtime Events')->schema([
                        TextEntry::make('reason')->badge(),
                        TextEntry::make('start_time')->dateTime(),
                        TextEntry::make('duration_minutes')->suffix(' min'),
                        TextEntry::make('root_cause')->placeholder('—'),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Fuel')->schema([
                    RepeatableEntry::make('fuelLogs')
                        ->label('Fuel Logs (Same Quarry & Date)')
                        ->getStateUsing(fn (ProductionLog $record) => FuelLog::query()
                            ->whereDate('date', $record->date)
                            ->when($record->company_id, fn ($q) => $q->where('company_id', $record->company_id))
                            ->limit(10)->get())
                        ->schema([
                            TextEntry::make('fuel_station')->label('Station')->placeholder('—'),
                            TextEntry::make('quantity_litres')->suffix(' L'),
                            TextEntry::make('total_cost'),
                        ])->columns(3)->columnSpanFull(),
                ]),

                Tab::make('Maintenance')->schema([
                    RepeatableEntry::make('maintenanceEvents')
                        ->label('Maintenance Events (Same Quarry & Date)')
                        ->getStateUsing(fn (ProductionLog $record) => Maintenance::query()
                            ->whereDate('maintenance_date', $record->date)
                            ->when($record->company_id, fn ($q) => $q->where('company_id', $record->company_id))
                            ->limit(10)->get())
                        ->schema([
                            TextEntry::make('maintenance_type'),
                            TextEntry::make('description')->placeholder('—'),
                            TextEntry::make('status')->badge(),
                        ])->columns(3)->columnSpanFull(),
                ]),

                Tab::make('Documents')->schema([
                    Section::make()->schema([
                        TextEntry::make('attachment_path')
                            ->label('Attachment')
                            ->formatStateUsing(fn ($state) => $state ? basename($state) : 'No document attached yet.')
                            ->url(fn (ProductionLog $record) => $record->attachment_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->attachment_path) : null)
                            ->openUrlInNewTab(),
                    ]),
                ]),

                Tab::make('Analytics')->schema([
                    Section::make()->schema([
                        TextEntry::make('yield_vs_site_average')
                            ->label('Yield % vs Site Average (30 Days)')
                            ->state(function (ProductionLog $record) {
                                $siteAvg = ProductionLog::query()
                                    ->where('extraction_site_id', $record->extraction_site_id)
                                    ->where('status', 'Approved')
                                    ->whereNotNull('yield_percent')
                                    ->where('date', '>=', now()->subDays(30))
                                    ->avg('yield_percent');

                                return ($record->yield_percent ?? 0) . '% (site avg: ' . round((float) $siteAvg, 1) . '%)';
                            }),
                    ]),
                ]),

                Tab::make('History')->schema([
                    RepeatableEntry::make('approvalRequests')->label('Approval History')->schema([
                        TextEntry::make('reference_number'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('submitted_at')->dateTime()->placeholder('—'),
                        TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                    ])->columns(4)->columnSpanFull(),
                ]),
            ])->columnSpanFull(),
        ]);
    }
}
