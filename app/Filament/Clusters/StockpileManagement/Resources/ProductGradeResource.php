<?php

namespace App\Filament\Clusters\StockpileManagement\Resources;

use App\Filament\Clusters\StockpileManagement;
use App\Filament\Clusters\StockpileManagement\Resources\ProductGradeResource\Pages;
use App\Models\Material;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The quarry finished-product catalog — a differently-scoped Resource over
 * the same Material table used construction-wide, filtered to rows that have
 * a grade set, extended with grade/selling_unit/quality_standard.
 */
class ProductGradeResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = StockpileManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'stockpile.view';
    }

    protected static ?string $model = Material::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'Product Grades';

    protected static ?int $navigationSort = 4;

    public const CATALOG_PRODUCTS = [
        'Crusher Dust', '0–5 mm', '5–10 mm', '10–20 mm', '20 mm Aggregate',
        '40 mm Aggregate', 'Ballast', 'Road Base', 'Hardcore', 'Gabion Stone', 'Rip Rap', 'Building Stone',
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Details')->schema([
                    Forms\Components\TextInput::make('material_code')->label('Product Code')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('name')->label('Product Name')->required(),
                    Forms\Components\TextInput::make('grade')
                        ->label('Grade')
                        ->datalist(self::CATALOG_PRODUCTS)
                        ->placeholder('e.g. 20 mm Aggregate, Crusher Dust, or a custom product')
                        ->required(),
                    Forms\Components\TextInput::make('unit')->label('Unit of Measure')->default('tonne')->required(),
                    Forms\Components\TextInput::make('selling_unit')->label('Selling Unit')->placeholder('e.g. per tonne, per load'),
                    Forms\Components\TextInput::make('quality_standard')->label('Quality Standard')->placeholder('e.g. KS 02-1070'),
                    Forms\Components\TextInput::make('selling_price')->label('Selling Price')->numeric()->prefix(session('currency', 'KES')),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereNotNull('grade'))
            ->columns([
                Tables\Columns\TextColumn::make('material_code')->label('Code')->searchable()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('name')->label('Product Name')->searchable(),
                Tables\Columns\TextColumn::make('grade')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('unit')->label('Unit of Measure'),
                Tables\Columns\TextColumn::make('selling_unit')->label('Selling Unit')->placeholder('—'),
                Tables\Columns\TextColumn::make('quality_standard')->label('Quality Standard')->placeholder('—'),
                Tables\Columns\TextColumn::make('selling_price')->money(session('currency', 'KES'))->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('grade')->options(array_combine(self::CATALOG_PRODUCTS, self::CATALOG_PRODUCTS)),
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
            'index' => Pages\ListProductGrades::route('/'),
            'create' => Pages\CreateProductGrade::route('/create'),
            'edit' => Pages\EditProductGrade::route('/{record}/edit'),
        ];
    }
}
