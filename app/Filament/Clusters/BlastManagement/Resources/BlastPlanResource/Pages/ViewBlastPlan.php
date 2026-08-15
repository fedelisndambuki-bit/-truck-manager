<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastPlanResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastPlanResource;
use App\Models\BlastPlan;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBlastPlan extends ViewRecord
{
    protected static string $resource = BlastPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Blast Plan')->tabs([
                Tab::make('Overview')->schema([
                    Section::make()->schema([
                        TextEntry::make('blast_number')->label('Blast #'),
                        TextEntry::make('blast_name'),
                        TextEntry::make('extractionSite.name')->label('Quarry'),
                        TextEntry::make('pit.name')->label('Pit')->placeholder('—'),
                        TextEntry::make('quarryFace.name')->label('Quarry Face')->placeholder('—'),
                        TextEntry::make('bench')->placeholder('—'),
                        TextEntry::make('blastEngineer.full_name')->label('Blast Engineer')->placeholder('—'),
                        TextEntry::make('date_planned')->date(),
                        TextEntry::make('priority')->badge(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => BlastPlanResource::getStatusColor($state)),
                    ])->columns(4),
                ]),

                Tab::make('Blast Design')->schema([
                    Section::make()->schema([
                        TextEntry::make('drill_pattern')->placeholder('—'),
                        TextEntry::make('burden')->suffix(' m')->placeholder('—'),
                        TextEntry::make('spacing')->suffix(' m')->placeholder('—'),
                        TextEntry::make('bench_height')->suffix(' m')->placeholder('—'),
                        TextEntry::make('sub_drill')->suffix(' m')->placeholder('—'),
                        TextEntry::make('scheduled_date')->date()->placeholder('—'),
                        TextEntry::make('scheduled_time')->time()->placeholder('—'),
                        TextEntry::make('safetyOfficer.full_name')->label('Safety Officer')->placeholder('—'),
                        TextEntry::make('security_team')->placeholder('—'),
                        TextEntry::make('emergency_team')->placeholder('—'),
                    ])->columns(4),
                ]),

                Tab::make('Drilling')->schema([
                    RepeatableEntry::make('drillHoles')->label('Drill Holes')->schema([
                        TextEntry::make('hole_number'),
                        TextEntry::make('diameter')->suffix(' mm')->placeholder('—'),
                        TextEntry::make('depth')->suffix(' m')->placeholder('—'),
                        TextEntry::make('drillRig.name')->label('Drill Rig')->placeholder('—'),
                        TextEntry::make('operator.full_name')->label('Operator')->placeholder('—'),
                    ])->columns(5)->columnSpanFull(),
                ]),

                Tab::make('Explosives')->schema([
                    RepeatableEntry::make('explosiveIssues')->label('Explosive Issues')->schema([
                        TextEntry::make('explosive_type'),
                        TextEntry::make('quantity_issued')->suffix(' kg'),
                        TextEntry::make('quantity_used')->suffix(' kg'),
                        TextEntry::make('remaining_quantity')->suffix(' kg'),
                        TextEntry::make('approval_status')->badge(),
                    ])->columns(5)->columnSpanFull(),
                ]),

                Tab::make('Safety')->schema([
                    Section::make()->schema([
                        TextEntry::make('safetyZone.safety_radius')->label('Safety Radius')->suffix(' m')->placeholder('—'),
                        TextEntry::make('safetyZone.warning_sirens')->label('Warning Sirens')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                        TextEntry::make('safetyZone.clearance_confirmed')->label('Clearance Confirmed')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                        TextEntry::make('safetyZone.restricted_areas')->label('Restricted Areas')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('safetyZone.evacuation_routes')->label('Evacuation Routes')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('safetyZone.assembly_points')->label('Assembly Points')->placeholder('—')->columnSpanFull(),
                    ])->columns(3),
                ]),

                Tab::make('Execution')->schema([
                    Section::make()->schema([
                        TextEntry::make('execution.actual_date')->label('Actual Date')->date()->placeholder('—'),
                        TextEntry::make('execution.responsibleEngineer.full_name')->label('Responsible Engineer')->placeholder('—'),
                        TextEntry::make('execution.shotFirer.full_name')->label('Shot Firer')->placeholder('—'),
                        TextEntry::make('execution.weather_conditions')->placeholder('—'),
                        TextEntry::make('execution.explosives_used')->label('Explosives Used')->suffix(' kg')->placeholder('—'),
                        TextEntry::make('execution.blast_outcome')->label('Outcome')->badge()->placeholder('—'),
                    ])->columns(3),
                ]),

                Tab::make('Post-Blast Analysis')->schema([
                    Section::make()->schema([
                        TextEntry::make('postBlastAnalysis.rock_fragmentation')->label('Rock Fragmentation')->placeholder('—'),
                        TextEntry::make('postBlastAnalysis.oversized_rocks')->label('Oversized Rocks')->suffix('%')->placeholder('—'),
                        TextEntry::make('postBlastAnalysis.fly_rock')->label('Fly Rock')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                        TextEntry::make('postBlastAnalysis.diggability')->placeholder('—'),
                        TextEntry::make('postBlastAnalysis.production_readiness')->placeholder('—'),
                        TextEntry::make('postBlastAnalysis.recommendations')->placeholder('—')->columnSpanFull(),
                    ])->columns(3),
                ]),

                Tab::make('Fragmentation')->schema([
                    Section::make()->schema([
                        TextEntry::make('fragmentationAnalysis.average_fragment_size')->label('Average Fragment')->suffix(' cm')->placeholder('—'),
                        TextEntry::make('fragmentationAnalysis.oversized_percentage')->label('Oversized %')->suffix('%')->placeholder('—'),
                        TextEntry::make('fragmentationAnalysis.fines_percentage')->label('Fines %')->suffix('%')->placeholder('—'),
                        TextEntry::make('fragmentationAnalysis.crusher_feed_suitability')->placeholder('—'),
                    ])->columns(4),
                ]),

                Tab::make('Environmental')->schema([
                    RepeatableEntry::make('environmentalReadings')->label('Readings')->schema([
                        TextEntry::make('ground_vibration')->label('Vibration')->suffix(' mm/s')->placeholder('—'),
                        TextEntry::make('air_overpressure')->label('Overpressure')->suffix(' dB')->placeholder('—'),
                        TextEntry::make('dust_level')->placeholder('—'),
                        TextEntry::make('compliance_status')->badge(),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Costs')->schema([
                    Section::make()->schema([
                        TextEntry::make('cost.drilling_cost')->label('Drilling')->placeholder('—'),
                        TextEntry::make('cost.explosive_cost')->label('Explosive')->placeholder('—'),
                        TextEntry::make('cost.labour_cost')->label('Labour')->placeholder('—'),
                        TextEntry::make('cost.equipment_cost')->label('Equipment')->placeholder('—'),
                        TextEntry::make('cost.fuel_cost')->label('Fuel')->placeholder('—'),
                        TextEntry::make('cost.security_cost')->label('Security')->placeholder('—'),
                        TextEntry::make('total_cost')->label('Total Cost')->state(fn (BlastPlan $record) => $record->cost ? number_format($record->cost->totalCost(), 2) : '—'),
                        TextEntry::make('cost_per_tonne')->label('Cost per Tonne')->state(fn (BlastPlan $record) => $record->cost?->costPerTonne() ?? '—'),
                    ])->columns(4),
                ]),

                Tab::make('Documents')->schema([
                    RepeatableEntry::make('documents')->label('Documents')->schema([
                        TextEntry::make('category')->badge(),
                        TextEntry::make('file_path')->label('File')->formatStateUsing(fn ($state) => $state ? basename($state) : '—'),
                        TextEntry::make('uploadedBy.name')->label('Uploaded By')->placeholder('—'),
                        TextEntry::make('created_at')->label('Uploaded')->dateTime(),
                    ])->columns(4)->columnSpanFull(),
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
                        TextEntry::make('total_holes')->label('Total Holes Drilled')->state(fn (BlastPlan $record) => $record->totalHoles()),
                        TextEntry::make('compliance_score')->label('Compliance Score')->state(fn (BlastPlan $record) => $record->compliance ? $record->compliance->scorePercent() . '%' : '—'),
                    ])->columns(2),
                ]),
            ])->columnSpanFull(),
        ]);
    }
}
