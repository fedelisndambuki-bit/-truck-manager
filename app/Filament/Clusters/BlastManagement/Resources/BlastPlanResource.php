<?php

namespace App\Filament\Clusters\BlastManagement\Resources;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Resources\BlastPlanResource\Pages;
use App\Models\BlastPlan;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlastPlanResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = BlastManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'blast.view';
    }

    protected static ?string $model = BlastPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Blast Plans';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['extractionSite', 'blastEngineer']);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(BlastPlan::STATUSES, BlastPlan::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Draft' => 'gray',
            'Submitted' => 'warning',
            'Approved' => 'info',
            'Executed' => 'success',
            'Closed' => 'success',
            'Cancelled' => 'danger',
            default => 'gray',
        };
    }

    public static function getPriorityOptions(): array
    {
        return array_combine(BlastPlan::PRIORITIES, BlastPlan::PRIORITIES);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment')->schema([
                    Forms\Components\TextInput::make('blast_number')->label('Blast #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('blast_name')->required(),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name')->searchable()->preload()->live()->required(),
                    Forms\Components\Select::make('pit_id')->label('Pit')->relationship('pit', 'name', fn ($query, Forms\Get $get) => $query->where('extraction_site_id', $get('extraction_site_id')))->searchable()->preload(),
                    Forms\Components\Select::make('quarry_face_id')->label('Quarry Face')->relationship('quarryFace', 'name', fn ($query, Forms\Get $get) => $query->where('extraction_site_id', $get('extraction_site_id')))->searchable()->preload(),
                    Forms\Components\TextInput::make('bench'),
                    Forms\Components\Select::make('blast_engineer_id')->label('Blast Engineer')->relationship('blastEngineer', 'full_name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('date_planned')->required()->default(now()),
                    Forms\Components\Select::make('priority')->options(self::getPriorityOptions())->default('Medium')->required(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Draft')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Blast Design')->schema([
                    Forms\Components\TextInput::make('drill_pattern'),
                    Forms\Components\TextInput::make('burden')->numeric()->suffix('m'),
                    Forms\Components\TextInput::make('spacing')->numeric()->suffix('m'),
                    Forms\Components\TextInput::make('bench_height')->numeric()->suffix('m'),
                    Forms\Components\TextInput::make('sub_drill')->numeric()->suffix('m'),
                ])->columns(3),

                Forms\Components\Section::make('Scheduling')->schema([
                    Forms\Components\DatePicker::make('scheduled_date'),
                    Forms\Components\TimePicker::make('scheduled_time'),
                    Forms\Components\Select::make('safety_officer_id')->label('Safety Officer')->relationship('safetyOfficer', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('blast_team')
                        ->label('Blast Team')
                        ->multiple()
                        ->options(fn () => \App\Models\Staff::query()->pluck('full_name', 'id'))
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('security_team'),
                    Forms\Components\TextInput::make('emergency_team'),
                ])->columns(3),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
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
                        TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('blast_number')->label('Blast #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('blast_name')->searchable(),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry')->toggleable(),
                Tables\Columns\TextColumn::make('blastEngineer.full_name')->label('Blast Engineer')->placeholder('—'),
                Tables\Columns\TextColumn::make('date_planned')->date()->sortable(),
                Tables\Columns\TextColumn::make('priority')->badge()->color(fn (string $state): string => match ($state) {
                    'Low' => 'gray', 'Medium' => 'info', 'High' => 'warning', 'Urgent' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->defaultSort('date_planned', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('priority')->options(self::getPriorityOptions()),
                Tables\Filters\SelectFilter::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (BlastPlan $record) => $record->status === 'Draft' && ! $record->isApprovalPending())
                    ->action(function (BlastPlan $record) {
                        $record->update(['status' => 'Submitted']);
                        $record->submitForApproval('blast-plan-approval', [
                            'title' => 'Blast Plan — ' . $record->blast_number,
                            'metadata' => ['blast_plan_id' => $record->id],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (BlastPlan $record) => $record->currentApproval() !== null)
                    ->url(fn (BlastPlan $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
                Tables\Actions\Action::make('markExecuted')
                    ->label('Mark Executed')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BlastPlan $record) => $record->status === 'Approved')
                    ->action(function (BlastPlan $record) {
                        $record->update(['status' => 'Executed']);
                        Notification::make()->title('Blast marked as executed — record execution details next')->success()->send();
                    }),
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (BlastPlan $record) => $record->status === 'Executed')
                    ->action(function (BlastPlan $record) {
                        $record->update(['status' => 'Closed']);
                        Notification::make()->title('Blast plan closed')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlastPlans::route('/'),
            'create' => Pages\CreateBlastPlan::route('/create'),
            'edit' => Pages\EditBlastPlan::route('/{record}/edit'),
        ];
    }
}
