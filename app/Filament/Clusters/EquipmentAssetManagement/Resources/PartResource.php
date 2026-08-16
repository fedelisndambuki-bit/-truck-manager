<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\PartResource\Pages;
use App\Models\Part;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartResource extends Resource
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

    protected static ?string $model = Part::class;

    protected static ?string $slug = 'spare-parts';

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static ?string $navigationLabel = 'Spare Parts';

    protected static ?int $navigationSort = 15;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['store']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Part Details')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('part_number')->required(),
                    Forms\Components\TextInput::make('category'),
                    Forms\Components\TextInput::make('unit_price')->numeric()->prefix(session('currency', 'KES'))->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(3),

                Forms\Components\Section::make('Stock')->schema([
                    Forms\Components\TextInput::make('stock_quantity')->numeric()->default(0)->required(),
                    Forms\Components\TextInput::make('min_stock')->numeric(),
                    Forms\Components\TextInput::make('max_stock')->numeric(),
                    Forms\Components\TextInput::make('reorder_level')->numeric()->default(5),
                    Forms\Components\Select::make('eam_store_id')->label('Store / Depot')->relationship('store', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('bin_location'),
                ])->columns(3),

                Forms\Components\Section::make('Supplier')->schema([
                    Forms\Components\TextInput::make('supplier'),
                    Forms\Components\TextInput::make('supplier_contact'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('part_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('category')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('store.name')->label('Store')->placeholder('—'),
                Tables\Columns\TextColumn::make('stock_quantity')->label('In Stock')->sortable()
                    ->color(fn (Part $record): string => $record->isBelowReorderLevel() ? 'danger' : 'success')
                    ->weight(fn (Part $record) => $record->isBelowReorderLevel() ? 'bold' : null),
                Tables\Columns\TextColumn::make('reorder_level')->label('Reorder At'),
                Tables\Columns\TextColumn::make('unit_price')->money(session('currency', 'KES')),
            ])
            ->filters([
                Tables\Filters\Filter::make('below_reorder')
                    ->label('Below Reorder Level')
                    ->query(fn ($query) => $query->whereColumn('stock_quantity', '<=', 'reorder_level')),
                Tables\Filters\SelectFilter::make('eam_store_id')->label('Store')->relationship('store', 'name'),
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
            'index' => Pages\ListParts::route('/'),
            'create' => Pages\CreatePart::route('/create'),
            'edit' => Pages\EditPart::route('/{record}/edit'),
        ];
    }
}
