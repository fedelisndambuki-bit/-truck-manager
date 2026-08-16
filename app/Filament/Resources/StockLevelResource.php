<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockLevelResource\Pages;
use App\Models\StockLevel;
use App\Traits\HasModuleAccess;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockLevelResource extends Resource
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

    protected static ?string $model = StockLevel::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Stock Levels';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['material', 'warehouse', 'project', 'site']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('material.material_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('material.name')->label('Material')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Warehouse')->sortable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('batch_number')->label('Batch')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('expiry_date')->date()->placeholder('—')->sortable()
                    ->color(fn (?string $state) => $state && \Carbon\Carbon::parse($state)->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('quantity_on_hand')->label('On Hand')->numeric(2)->sortable(),
                Tables\Columns\TextColumn::make('reserved_quantity')->label('Reserved')->numeric(2),
                Tables\Columns\TextColumn::make('available_quantity')->label('Available')->numeric(2)
                    ->state(fn (StockLevel $record) => $record->available_quantity),
                Tables\Columns\TextColumn::make('damaged_quantity')->label('Damaged')->numeric(2)->color(fn ($state) => $state > 0 ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')->label('Warehouse')->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('material_id')->label('Material')->relationship('material', 'name')->searchable(),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring in 30 days')
                    ->query(fn ($query) => $query->whereBetween('expiry_date', [now(), now()->addDays(30)])),
            ])
            ->defaultSort('quantity_on_hand', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockLevels::route('/'),
        ];
    }
}
