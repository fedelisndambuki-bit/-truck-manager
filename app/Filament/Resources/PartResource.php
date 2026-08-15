<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartResource\Pages;
use App\Models\Part;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Traits\HasModuleAccess;

class PartResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'inventory';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }

    protected static ?string $model = Part::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Inventory';

    public static function canViewAny(): bool
    {
        // Was previously hardcoded to bypass the module/permission gate; now
        // still bypasses permissions but must still respect the industry gate.
        return static::passesIndustryGate();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Part Information')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('part_number')->required()->unique(ignoreRecord: true),
                    Forms\Components\Select::make('category')
                        ->options([
                            'Engine' => 'Engine',
                            'Brakes' => 'Brakes',
                            'Suspension' => 'Suspension',
                            'Electrical' => 'Electrical',
                            'Body' => 'Body',
                            'Tyres' => 'Tyres',
                            'Filters' => 'Filters',
                            'Fluids' => 'Fluids',
                        ])->searchable(),
                ])->columns(3),

                Forms\Components\Section::make('Stock Levels')->schema([
                    Forms\Components\TextInput::make('unit_price')->numeric()->prefix(session('currency', 'KES'))->required(),
                    Forms\Components\TextInput::make('stock_quantity')->numeric()->required()->default(0),
                    Forms\Components\TextInput::make('reorder_level')->numeric()->required()->default(5),
                ])->columns(3),

                Forms\Components\Textarea::make('description')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('part_number')->searchable(),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('unit_price')->money(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->badge()
                    ->color(fn ($record) => $record->stock_quantity <= $record->reorder_level ? 'danger' : 'success')
                    ->label('In Stock'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options([
                    'Engine' => 'Engine',
                    'Brakes' => 'Brakes',
                    'Suspension' => 'Suspension',
                    'Electrical' => 'Electrical',
                    'Body' => 'Body',
                    'Tyres' => 'Tyres',
                    'Filters' => 'Filters',
                    'Fluids' => 'Fluids',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])->striped();
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
