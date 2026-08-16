<?php

namespace App\Filament\Clusters\ProductionManagement\Resources;

use App\Filament\Clusters\ProductionManagement;
use App\Filament\Clusters\ProductionManagement\Resources\ProductionRecordResource\Pages;
use App\Models\FuelLog;
use App\Models\Maintenance;
use App\Models\ProductionLog;
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

class ProductionRecordResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = ProductionManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'production.view';
    }

    protected static ?string $model = ProductionLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Production Records';

    protected static ?int $navigationSort = 2;

    public static function getStatusOptions(): array
    {
        return array_combine(ProductionLog::STATUSES, ProductionLog::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Draft' => 'gray',
            'Submitted' => 'warning',
            'Approved' => 'success',
            'Closed' => 'info',
            default => 'gray',
        };
    }

    public static function getMaterialTypeOptions(): array
    {
        return [
            'Granite' => 'Granite', 'Limestone' => 'Limestone', 'Sand' => 'Sand',
            'Gravel' => 'Gravel', 'Other' => 'Other',
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['extractionSite', 'crushingPlant', 'supervisor']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment')->schema([
                    Forms\Components\TextInput::make('production_number')->label('Production #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name')->searchable()->preload()->live()->required(),
                    Forms\Components\Select::make('quarry_face_id')->label('Quarry Face')->relationship('quarryFace', 'name', fn ($query, Forms\Get $get) => $query->where('extraction_site_id', $get('extraction_site_id')))->searchable()->preload(),
                    Forms\Components\Select::make('pit_id')->label('Pit')->relationship('pit', 'name', fn ($query, Forms\Get $get) => $query->where('extraction_site_id', $get('extraction_site_id')))->searchable()->preload(),
                    Forms\Components\Select::make('crushing_plant_id')->label('Crushing Plant')->relationship('crushingPlant', 'name', fn ($query, Forms\Get $get) => $query->where('extraction_site_id', $get('extraction_site_id')))->searchable()->preload()->live(),
                    Forms\Components\Select::make('production_line_id')->label('Production Line')->relationship('productionLine', 'name', fn ($query, Forms\Get $get) => $query->where('crushing_plant_id', $get('crushing_plant_id')))->searchable()->preload(),
                    Forms\Components\Select::make('shift_id')->label('Shift')->relationship('shift', 'name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('date')->required()->default(now()),
                    Forms\Components\Select::make('supervisor_id')->label('Supervisor')->relationship('supervisor', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('operator_id')->label('Operator')->relationship('operator', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('logged_by_id')->label('Logged By')->relationship('loggedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Draft')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Production Details')->schema([
                    Forms\Components\Select::make('material_type')->options(self::getMaterialTypeOptions())->required(),
                    Forms\Components\TextInput::make('quantity_tonnes')->label('Raw Material Extracted')->numeric()->suffix('t')->required(),
                    Forms\Components\TextInput::make('feed_quantity')->label('Feed Quantity')->numeric()->suffix('t'),
                    Forms\Components\TextInput::make('crusher_throughput')->label('Crusher Throughput')->numeric()->suffix('t'),
                    Forms\Components\TextInput::make('finished_product_quantity')->label('Finished Product Quantity')->numeric()->suffix('t'),
                    Forms\Components\TextInput::make('rejected_material')->label('Rejected Material')->numeric()->suffix('t'),
                    Forms\Components\TextInput::make('waste_material')->label('Waste Material')->numeric()->suffix('t'),
                    Forms\Components\TextInput::make('by_products')->label('By-products')->numeric()->suffix('t'),
                    Forms\Components\TextInput::make('recovery_percent')->label('Recovery %')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('yield_percent')->label('Yield %')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('operating_hours')->numeric()->suffix('hrs'),
                    Forms\Components\TextInput::make('idle_hours')->numeric()->suffix('hrs'),
                    Forms\Components\TextInput::make('downtime_minutes')->label('Downtime')->numeric()->suffix('min')->default(0),
                    Forms\Components\Textarea::make('remarks')->columnSpanFull(),
                    Forms\Components\FileUpload::make('attachment_path')->label('Attachment')->disk('public')->directory('production-attachments')->columnSpanFull(),
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
            Tabs::make('Production Record')->tabs([
                Tab::make('Overview')->schema([
                    Section::make()->schema([
                        TextEntry::make('production_number')->label('Production #'),
                        TextEntry::make('extractionSite.name')->label('Quarry'),
                        TextEntry::make('date')->date(),
                        TextEntry::make('shift')->badge(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('production_number')->label('Production #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry')->toggleable(),
                Tables\Columns\TextColumn::make('crushingPlant.name')->label('Plant')->toggleable(),
                Tables\Columns\TextColumn::make('material_type')->badge(),
                Tables\Columns\TextColumn::make('quantity_tonnes')->numeric()->suffix(' t')->label('Quantity'),
                Tables\Columns\TextColumn::make('shift')->badge(),
                Tables\Columns\TextColumn::make('supervisor.full_name')->label('Supervisor')->toggleable()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('shift')->options(['Day' => 'Day', 'Night' => 'Night']),
                Tables\Filters\SelectFilter::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (ProductionLog $record) => $record->status === 'Draft' && ! $record->isApprovalPending())
                    ->action(function (ProductionLog $record) {
                        $record->update(['status' => 'Submitted']);
                        $record->submitForApproval('production-record-approval', [
                            'title' => 'Production Record — ' . $record->production_number,
                            'metadata' => ['production_log_id' => $record->id],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (ProductionLog $record) => $record->currentApproval() !== null)
                    ->url(fn (ProductionLog $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (ProductionLog $record) => $record->status === 'Approved')
                    ->action(function (ProductionLog $record) {
                        $record->update(['status' => 'Closed']);
                        Notification::make()->title('Production record closed')->success()->send();
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
            'index' => Pages\ListProductionRecords::route('/'),
            'create' => Pages\CreateProductionRecord::route('/create'),
            'edit' => Pages\EditProductionRecord::route('/{record}/edit'),
        ];
    }
}
