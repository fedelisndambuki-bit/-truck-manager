<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Resources\ProductionRunResource\Pages;
use App\Models\ProductionRun;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionRuns::route('/'),
            'create' => Pages\CreateProductionRun::route('/create'),
            'view' => Pages\ViewProductionRun::route('/{record}'),
            'edit' => Pages\EditProductionRun::route('/{record}/edit'),
        ];
    }
}
