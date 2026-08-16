<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhysicalStockCountResource\Pages;
use App\Models\PhysicalStockCount;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PhysicalStockCountResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'materials.count';
    }

    protected static ?string $model = PhysicalStockCount::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Physical Stock Count';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 18;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['warehouse']);
    }

    public static function headerSchema(): array
    {
        return [
            Forms\Components\Select::make('warehouse_id')
                ->label('Warehouse')
                ->relationship('warehouse', 'name')
                ->searchable()->preload()->required(),
            Forms\Components\DatePicker::make('scheduled_date')->default(now()),
        ];
    }

    public static function itemsSchema(): array
    {
        return [
            Forms\Components\Repeater::make('items')
                ->relationship('items')
                ->schema([
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()->preload()->required()
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('expected_quantity')->numeric()->disabled()->dehydrated(),
                    Forms\Components\TextInput::make('counted_quantity')->numeric(),
                ])
                ->columns(4)
                ->columnSpanFull()
                ->addActionLabel('Add Material'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Count Details')->schema(self::headerSchema())->columns(2),
                Forms\Components\Section::make('Items')->schema(self::itemsSchema()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('count_number')->label('Count #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Warehouse')->searchable(),
                Tables\Columns\TextColumn::make('scheduled_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Materials Counted'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Completed' => 'success', 'In Progress' => 'warning', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Scheduled' => 'Scheduled', 'In Progress' => 'In Progress', 'Completed' => 'Completed',
                ]),
                Tables\Filters\SelectFilter::make('warehouse_id')->label('Warehouse')->relationship('warehouse', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhysicalStockCounts::route('/'),
            'create' => Pages\CreatePhysicalStockCount::route('/create'),
            'edit' => Pages\EditPhysicalStockCount::route('/{record}/edit'),
        ];
    }
}
