<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Resources\ProductionRunResource\Pages;
use App\Models\EnergyReading;
use App\Models\Maintenance;
use App\Models\PlantDocument;
use App\Models\ProductionRun;
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

class ProductionRunResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = CrushingPlantManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'plant.view';
    }

    protected static ?string $model = ProductionRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationLabel = 'Production Runs';

    protected static ?int $navigationSort = 8;

    public static function getStatusOptions(): array
    {
        return array_combine(ProductionRun::STATUSES, ProductionRun::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Draft' => 'gray',
            'Submitted' => 'warning',
            'Approved' => 'info',
            'Rejected' => 'danger',
            'Completed' => 'success',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Run Details')->schema([
                    Forms\Components\TextInput::make('run_number')->label('Run #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('crushing_plant_id')->label('Plant')->relationship('crushingPlant', 'name')->searchable()->preload()->live()->required(),
                    Forms\Components\Select::make('production_line_id')->label('Production Line')->relationship('productionLine', 'name', fn ($query, Forms\Get $get) => $query->where('crushing_plant_id', $get('crushing_plant_id')))->searchable()->preload(),
                    Forms\Components\Select::make('material_id')->label('Product')->relationship('material', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('input_quantity')->numeric()->suffix('t')->required(),
                    Forms\Components\TextInput::make('output_quantity')->numeric()->suffix('t')->required(),
                    Forms\Components\DateTimePicker::make('start_time'),
                    Forms\Components\DateTimePicker::make('finish_time'),
                    Forms\Components\Select::make('supervisor_id')->label('Supervisor')->relationship('supervisor', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Draft')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Product Output')->schema([
                    Forms\Components\Repeater::make('outputs')->relationship('outputs')->label('Products')->schema([
                        Forms\Components\Select::make('material_id')->label('Product')->relationship('material', 'name')->searchable()->preload()->required(),
                        Forms\Components\TextInput::make('quantity_produced')->numeric()->suffix('t')->required(),
                        Forms\Components\TextInput::make('unit')->default('tonne'),
                        Forms\Components\Select::make('stockpile_id')->label('Destination Stockpile')->relationship('stockpile', 'label')->searchable()->preload(),
                        Forms\Components\TextInput::make('quality_grade'),
                    ])->columns(4)->columnSpanFull()->defaultItems(0),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
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
                        TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
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
                        TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                    ])->columns(3),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('run_number')->label('Run #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('crushingPlant.name')->label('Plant'),
                Tables\Columns\TextColumn::make('productionLine.name')->label('Line')->placeholder('—'),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->placeholder('—'),
                Tables\Columns\TextColumn::make('input_quantity')->label('Input')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('output_quantity')->label('Output')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('yield')
                    ->label('Yield %')
                    ->state(fn (ProductionRun $record): string => $record->yieldPercent() . '%'),
                Tables\Columns\TextColumn::make('supervisor.full_name')->label('Supervisor')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('crushing_plant_id')->label('Plant')->relationship('crushingPlant', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (ProductionRun $record) => $record->status === 'Draft' && ! $record->isApprovalPending())
                    ->action(function (ProductionRun $record) {
                        $record->update(['status' => 'Submitted']);
                        $record->submitForApproval('production-run-approval', [
                            'title' => 'Production Run — ' . $record->run_number,
                            'metadata' => ['production_run_id' => $record->id],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (ProductionRun $record) => $record->currentApproval() !== null)
                    ->url(fn (ProductionRun $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
                Tables\Actions\Action::make('markCompleted')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ProductionRun $record) => $record->status === 'Approved')
                    ->action(function (ProductionRun $record) {
                        $record->update(['status' => 'Completed']);
                        Notification::make()->title('Production run completed')->success()->send();
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['crushingPlant', 'productionLine', 'material', 'supervisor']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionRuns::route('/'),
            'create' => Pages\CreateProductionRun::route('/create'),
            'edit' => Pages\EditProductionRun::route('/{record}/edit'),
        ];
    }
}
