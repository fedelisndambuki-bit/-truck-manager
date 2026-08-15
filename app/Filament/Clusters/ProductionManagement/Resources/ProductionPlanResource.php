<?php

namespace App\Filament\Clusters\ProductionManagement\Resources;

use App\Filament\Clusters\ProductionManagement;
use App\Filament\Clusters\ProductionManagement\Resources\ProductionPlanResource\Pages;
use App\Models\ProductionPlan;
use App\Models\Staff;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductionPlanResource extends Resource
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

    protected static ?string $model = ProductionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Production Planning';

    protected static ?int $navigationSort = 1;

    public static function getPriorityOptions(): array
    {
        return array_combine(ProductionPlan::PRIORITIES, ProductionPlan::PRIORITIES);
    }

    public static function getPriorityColor(string $state): string
    {
        return match ($state) {
            'Low' => 'gray',
            'Medium' => 'info',
            'High' => 'warning',
            'Urgent' => 'danger',
            default => 'gray',
        };
    }

    public static function getStatusOptions(): array
    {
        return array_combine(ProductionPlan::STATUSES, ProductionPlan::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Planned' => 'gray',
            'In Progress' => 'warning',
            'Completed' => 'success',
            'Cancelled' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Details')->schema([
                    Forms\Components\TextInput::make('plan_number')->label('Plan #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('crushing_plant_id')->label('Plant')->relationship('crushingPlant', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('material_id')->label('Product')->relationship('material', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('target_quantity')->numeric()->suffix('t')->required(),
                    Forms\Components\DatePicker::make('planned_date')->required()->default(now()),
                    Forms\Components\Select::make('priority')->options(self::getPriorityOptions())->default('Medium')->required(),
                    Forms\Components\Select::make('supervisor_id')->label('Assigned Supervisor')->relationship('supervisor', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('equipment_id')->label('Assigned Equipment')->relationship('equipment', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('assigned_crew')
                        ->label('Assigned Crew')
                        ->multiple()
                        ->options(fn () => Staff::query()->pluck('full_name', 'id'))
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Planned')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plan_number')->label('Plan #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry'),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->placeholder('—'),
                Tables\Columns\TextColumn::make('target_quantity')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('planned_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('priority')->badge()->color(fn (string $state): string => self::getPriorityColor($state)),
                Tables\Columns\TextColumn::make('supervisor.full_name')->label('Supervisor')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->defaultSort('planned_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('priority')->options(self::getPriorityOptions()),
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
            ])
            ->actions([
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
            'index' => Pages\ListProductionPlans::route('/'),
            'create' => Pages\CreateProductionPlan::route('/create'),
            'edit' => Pages\EditProductionPlan::route('/{record}/edit'),
        ];
    }
}
