<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\AssetRegisterResource\Pages;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentResource;
use App\Models\Equipment;
use App\Traits\HasModuleAccess;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Finance-oriented view of the same Equipment table as the Equipment
 * Register — asset cost basis, book value, depreciation inputs. Read-mostly
 * (view only); asset records themselves are created/edited from the
 * Equipment Register to avoid maintaining the record through two forms.
 */
class AssetRegisterResource extends Resource
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

    protected static ?string $model = Equipment::class;

    protected static ?string $slug = 'asset-register';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Asset Register';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['category']);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Asset Identification')->schema([
                TextEntry::make('equipment_code')->label('Equipment Number'),
                TextEntry::make('asset_number')->placeholder('—'),
                TextEntry::make('name'),
                TextEntry::make('category.name')->label('Category')->placeholder('—'),
                TextEntry::make('status')->badge()->color(fn (string $state): string => EquipmentResource::getStatusColor($state)),
            ])->columns(4),

            InfolistSection::make('Cost Basis & Valuation')->schema([
                TextEntry::make('purchase_date')->date()->placeholder('—'),
                TextEntry::make('commission_date')->date()->placeholder('—'),
                TextEntry::make('supplier')->placeholder('—'),
                TextEntry::make('purchase_cost')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('replacement_cost')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('current_value')->label('Current Book Value')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('useful_life_years')->label('Useful Life (Years)')->placeholder('—'),
                TextEntry::make('residual_value')->money(session('currency', 'KES'))->placeholder('—'),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment_code')->label('Equip. No.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('asset_number')->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->placeholder('—'),
                Tables\Columns\TextColumn::make('purchase_date')->date()->placeholder('—'),
                Tables\Columns\TextColumn::make('purchase_cost')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('replacement_cost')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('current_value')->label('Book Value')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('useful_life_years')->label('Useful Life')->suffix(' yrs')->placeholder('—'),
                Tables\Columns\TextColumn::make('residual_value')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => EquipmentResource::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(EquipmentResource::getStatusOptions()),
                Tables\Filters\SelectFilter::make('equipment_category_id')->label('Category')->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetRegister::route('/'),
        ];
    }
}
