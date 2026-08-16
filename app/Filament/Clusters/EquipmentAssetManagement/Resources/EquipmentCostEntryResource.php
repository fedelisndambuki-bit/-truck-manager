<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentCostEntryResource\Pages;
use App\Models\EquipmentCostEntry;
use App\Traits\HasModuleAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only view of the unified cost ledger — every row here is written
 * automatically by MaintenanceObserver/FuelLogObserver/TyreRepairObserver/
 * MaintenanceWorkOrderService, never entered by hand.
 */
class EquipmentCostEntryResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = EquipmentAssetManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'eam.view';
    }

    protected static ?string $model = EquipmentCostEntry::class;

    protected static ?string $slug = 'costs';

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Costs';

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['equipment']);
    }

    public static function getCategoryColor(string $state): string
    {
        return match ($state) {
            'Fuel' => 'warning', 'Maintenance' => 'danger', 'Tyre' => 'info',
            'Parts' => 'gray', 'Depreciation' => 'gray', default => 'gray',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment.name')->label('Equipment')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('cost_category')->label('Category')->badge()->color(fn (string $state): string => self::getCategoryColor($state)),
                Tables\Columns\TextColumn::make('amount')->money(session('currency', 'KES'))->sortable(),
                Tables\Columns\TextColumn::make('incurred_on')->date()->sortable(),
                Tables\Columns\TextColumn::make('description')->limit(60)->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cost_category')->label('Category')->options(array_combine(EquipmentCostEntry::CATEGORIES, EquipmentCostEntry::CATEGORIES)),
                Tables\Filters\SelectFilter::make('equipment_id')->label('Equipment')->relationship('equipment', 'name'),
            ])
            ->defaultSort('incurred_on', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipmentCostEntries::route('/'),
        ];
    }
}
