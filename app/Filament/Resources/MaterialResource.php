<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialResource\Pages;
use App\Models\Material;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MaterialResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'materials.view';
    }

    protected static ?string $model = Material::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Materials';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['category', 'preferredSupplier']);
    }

    public static function getUnitOptions(): array
    {
        return [
            'bags' => 'Bags',
            'tonnes' => 'Tonnes',
            'm3' => 'Cubic Metres (m³)',
            'pieces' => 'Pieces',
            'litres' => 'Litres',
            'metres' => 'Metres',
            'rolls' => 'Rolls',
            'sheets' => 'Sheets',
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Active' => 'success',
            'Inactive' => 'gray',
            'Discontinued' => 'danger',
            default => 'gray',
        };
    }

    public static function getStockStatusColor(string $status): string
    {
        return match ($status) {
            'In Stock' => 'success',
            'Low Stock' => 'warning',
            'Out of Stock' => 'danger',
            default => 'gray',
        };
    }

    public static function basicInfoSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Select::make('category_id')
                ->label('Category')
                ->relationship('category', 'name', fn ($query) => $query->whereNull('parent_id'))
                ->searchable()->preload()
                ->createOptionForm([Forms\Components\TextInput::make('name')->required()]),
            Forms\Components\Select::make('subcategory_id')
                ->label('Subcategory')
                ->relationship('subcategory', 'name', fn ($query) => $query->whereNotNull('parent_id'))
                ->searchable()->preload(),
            Forms\Components\Select::make('unit')->label('Unit of Measure')->options(self::getUnitOptions())->default('bags')->required(),
            Forms\Components\Select::make('status')->options([
                'Active' => 'Active', 'Inactive' => 'Inactive', 'Discontinued' => 'Discontinued',
            ])->default('Active')->required(),
            Forms\Components\Textarea::make('description')->columnSpanFull(),
        ];
    }

    public static function supplierCostSchema(): array
    {
        return [
            Forms\Components\Select::make('preferred_supplier_id')
                ->label('Preferred Supplier')
                ->relationship('preferredSupplier', 'company_name')
                ->searchable()->preload(),
            Forms\Components\TextInput::make('unit_cost')->label('Unit Cost')->numeric()->prefix(session('currency', 'KES')),
            Forms\Components\TextInput::make('standard_cost')->numeric()->prefix(session('currency', 'KES')),
            Forms\Components\TextInput::make('average_cost')->numeric()->prefix(session('currency', 'KES')),
            Forms\Components\TextInput::make('selling_price')->numeric()->prefix(session('currency', 'KES')),
        ];
    }

    public static function stockLevelsSchema(): array
    {
        return [
            Forms\Components\TextInput::make('quantity_on_hand')->label('Opening Quantity on Hand')->numeric()->default(0),
            Forms\Components\TextInput::make('reorder_level')->numeric()->helperText('Alert when stock falls below this level'),
            Forms\Components\TextInput::make('min_stock')->label('Minimum Stock'),
            Forms\Components\TextInput::make('max_stock')->label('Maximum Stock'),
        ];
    }

    public static function specsDocumentsSchema(): array
    {
        return [
            Forms\Components\Textarea::make('specification')->label('Material Specification')->columnSpanFull(),
            Forms\Components\FileUpload::make('images')
                ->image()
                ->multiple()
                ->disk('public')
                ->directory('material-images')
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('datasheet_path')
                ->label('Technical Datasheet')
                ->disk('public')
                ->directory('material-datasheets'),
            Forms\Components\FileUpload::make('safety_datasheet_path')
                ->label('Safety Datasheet (MSDS)')
                ->disk('public')
                ->directory('material-safety-datasheets'),
        ];
    }

    public static function identificationSchema(): array
    {
        return [
            Forms\Components\TextInput::make('barcode'),
            Forms\Components\Placeholder::make('qr_code_note')
                ->label('QR Code')
                ->content('A scannable QR code encoding the material code is generated automatically once this material is saved.'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Material')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Info')->schema(self::basicInfoSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Supplier & Cost')->schema(self::supplierCostSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Stock Levels')->schema(self::stockLevelsSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Specs & Documents')->schema(self::specsDocumentsSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Identification')->schema(self::identificationSchema())->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('material_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('unit')->label('UOM')->toggleable(),
                Tables\Columns\TextColumn::make('quantity_on_hand')->label('On Hand')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Stock Status')
                    ->badge()
                    ->color(fn (Material $record) => self::getStockStatusColor($record->stock_status)),
                Tables\Columns\TextColumn::make('inventory_value')->label('Inventory Value')->money(session('currency', 'KES'))->state(fn (Material $record) => $record->inventory_value),
                Tables\Columns\TextColumn::make('preferredSupplier.company_name')->label('Preferred Supplier')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')->label('Category')->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive', 'Discontinued' => 'Discontinued']),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low / Out of Stock')
                    ->query(fn ($query) => $query->whereColumn('quantity_on_hand', '<=', 'reorder_level')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalIcon('heroicon-o-eye')
                    ->modalIconColor('gray'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
            'view' => Pages\ViewMaterial::route('/{record}'),
        ];
    }
}
