<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\ProductionRunResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\ProductionRunResource;
use App\Models\EnergyReading;
use App\Models\Maintenance;
use App\Models\PlantDocument;
use App\Models\ProductionRun;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewProductionRun extends ViewRecord
{
    protected static string $resource = ProductionRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Production Run')->tabs([
                Tab::make('Overview')->schema([
                    Section::make()->schema([
                        TextEntry::make('run_number')->label('Run #'),
                        TextEntry::make('crushingPlant.name')->label('Plant'),
                        TextEntry::make('productionLine.name')->label('Production Line')->placeholder('—'),
                        TextEntry::make('material.name')->label('Product')->placeholder('—'),
                        TextEntry::make('input_quantity')->label('Input Quantity')->suffix(' t'),
                        TextEntry::make('output_quantity')->label('Output Quantity')->suffix(' t'),
                        TextEntry::make('supervisor.full_name')->label('Supervisor')->placeholder('—'),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => ProductionRunResource::getStatusColor($state)),
                    ])->columns(4),
                ]),

                Tab::make('Equipment')->schema([
                    RepeatableEntry::make('productionLine.crushers')->label('Crushers')->schema([
                        TextEntry::make('crusher_code')->label('Code'),
                        TextEntry::make('type'),
                        TextEntry::make('status')->badge(),
                    ])->columns(3)->columnSpanFull(),
                    RepeatableEntry::make('productionLine.screens')->label('Screens')->schema([
                        TextEntry::make('name'),
                        TextEntry::make('status')->badge(),
                    ])->columns(2)->columnSpanFull(),
                ]),

                Tab::make('Production')->schema([
                    Section::make()->schema([
                        TextEntry::make('start_time')->dateTime()->placeholder('—'),
                        TextEntry::make('finish_time')->dateTime()->placeholder('—'),
                        TextEntry::make('duration')->label('Duration')->state(fn (ProductionRun $record) => $record->durationMinutes() !== null ? $record->durationMinutes() . ' min' : '—'),
                        TextEntry::make('yield')->label('Yield %')->state(fn (ProductionRun $record) => $record->yieldPercent() . '%'),
                        TextEntry::make('waste')->label('Waste %')->state(fn (ProductionRun $record) => $record->wastePercent() . '%'),
                        TextEntry::make('recovery')->label('Recovery %')->state(fn (ProductionRun $record) => $record->recoveryPercent() . '%'),
                    ])->columns(3),
                ]),

                Tab::make('Products')->schema([
                    RepeatableEntry::make('outputs')->label('Product Output')->schema([
                        TextEntry::make('material.name')->label('Product'),
                        TextEntry::make('quantity_produced')->suffix(fn ($record) => ' ' . ($record->unit ?? 'tonne')),
                        TextEntry::make('stockpile.label')->label('Destination Stockpile')->placeholder('—'),
                        TextEntry::make('quality_grade')->placeholder('—'),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Downtime')->schema([
                    RepeatableEntry::make('downtimeEvents')->label('Related Downtime (Same Plant/Line)')
                        ->getStateUsing(fn (ProductionRun $record) => $record->relatedDowntimes())
                        ->schema([
                            TextEntry::make('reason')->badge(),
                            TextEntry::make('start_time')->dateTime(),
                            TextEntry::make('duration_minutes')->suffix(' min'),
                        ])->columns(3)->columnSpanFull(),
                ]),

                Tab::make('Maintenance')->schema([
                    RepeatableEntry::make('maintenanceEvents')
                        ->label('Maintenance Events (Same Plant, Recent)')
                        ->getStateUsing(fn (ProductionRun $record) => Maintenance::query()
                            ->when($record->company_id, fn ($q) => $q->where('company_id', $record->company_id))
                            ->whereIn('equipment_id', $record->productionLine
                                ? $record->productionLine->crushers()->pluck('equipment_id')->merge($record->productionLine->screens()->pluck('equipment_id'))->filter()->all()
                                : [])
                            ->limit(10)->get())
                        ->schema([
                            TextEntry::make('maintenance_type'),
                            TextEntry::make('description')->placeholder('—'),
                            TextEntry::make('status')->badge(),
                        ])->columns(3)->columnSpanFull(),
                ]),

                Tab::make('Energy')->schema([
                    RepeatableEntry::make('energyReadings')
                        ->label('Energy Readings (Same Plant, Recent)')
                        ->getStateUsing(fn (ProductionRun $record) => EnergyReading::query()
                            ->where('crushing_plant_id', $record->crushing_plant_id)
                            ->latest('date')->limit(5)->get())
                        ->schema([
                            TextEntry::make('date')->date(),
                            TextEntry::make('electricity_kwh')->label('Electricity')->suffix(' kWh')->placeholder('—'),
                            TextEntry::make('diesel_litres')->label('Diesel')->suffix(' L')->placeholder('—'),
                        ])->columns(3)->columnSpanFull(),
                ]),

                Tab::make('Quality')->schema([
                    RepeatableEntry::make('qualityTests')->label('Quality Tests')->schema([
                        TextEntry::make('sample_number'),
                        TextEntry::make('pass_fail')->label('Result')->formatStateUsing(fn ($state) => $state ? 'Pass' : 'Fail')->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger'),
                        TextEntry::make('approved_for_dispatch')->label('Approved for Dispatch')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                    ])->columns(3)->columnSpanFull(),
                ]),

                Tab::make('Stockpiles')->schema([
                    RepeatableEntry::make('outputs')->label('Destination Stockpiles')->schema([
                        TextEntry::make('stockpile.label')->label('Stockpile')->placeholder('—'),
                        TextEntry::make('stockpile.quantity_available')->label('Current Available')->suffix(' t')->placeholder('—'),
                        TextEntry::make('quantity_produced')->label('Credited This Run')->suffix(' t'),
                    ])->columns(3)->columnSpanFull(),
                ]),

                Tab::make('Documents')->schema([
                    RepeatableEntry::make('plantDocuments')
                        ->label('Plant Documents')
                        ->getStateUsing(fn (ProductionRun $record) => PlantDocument::query()->where('crushing_plant_id', $record->crushing_plant_id)->limit(10)->get())
                        ->schema([
                            TextEntry::make('category')->badge(),
                            TextEntry::make('file_path')->label('File')->formatStateUsing(fn ($state) => $state ? basename($state) : '—'),
                        ])->columns(2)->columnSpanFull(),
                ]),

                Tab::make('Approvals')->schema([
                    RepeatableEntry::make('approvalRequests')->label('Approval History')->schema([
                        TextEntry::make('reference_number'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('submitted_at')->dateTime()->placeholder('—'),
                        TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Analytics')->schema([
                    Section::make()->schema([
                        TextEntry::make('yield_vs_avg')
                            ->label('Yield % vs Plant Average (30 Days)')
                            ->state(function (ProductionRun $record) {
                                $avg = ProductionRun::query()
                                    ->where('crushing_plant_id', $record->crushing_plant_id)
                                    ->whereIn('status', ['Approved', 'Completed'])
                                    ->where('created_at', '>=', now()->subDays(30))
                                    ->get()
                                    ->avg(fn (ProductionRun $r) => $r->yieldPercent());

                                return $record->yieldPercent() . '% (plant avg: ' . round((float) $avg, 1) . '%)';
                            }),
                    ]),
                ]),

                Tab::make('History')->schema([
                    Section::make()->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->label('Last Updated')->dateTime(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => ProductionRunResource::getStatusColor($state)),
                    ])->columns(3),
                ]),
            ])->columnSpanFull(),
        ]);
    }
}
