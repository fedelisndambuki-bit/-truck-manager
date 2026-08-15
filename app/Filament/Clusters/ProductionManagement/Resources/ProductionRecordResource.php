<?php

namespace App\Filament\Clusters\ProductionManagement\Resources;

use App\Filament\Clusters\ProductionManagement;
use App\Filament\Clusters\ProductionManagement\Resources\ProductionRecordResource\Pages;
use App\Models\ProductionLog;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
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
            'view' => Pages\ViewProductionRecord::route('/{record}'),
            'edit' => Pages\EditProductionRecord::route('/{record}/edit'),
        ];
    }
}
