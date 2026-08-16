<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages;
use App\Models\Position;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PositionResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'hr.view';
    }

    protected static ?string $model = Position::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 5;

    public static function getLevelOptions(): array
    {
        return [
            'Entry' => 'Entry',
            'Mid' => 'Mid',
            'Senior' => 'Senior',
            'Management' => 'Management',
            'Executive' => 'Executive',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Position Details')->schema([
                    Forms\Components\TextInput::make('title')->required(),
                    Forms\Components\Select::make('department_id')
                        ->label('Department')
                        ->relationship('department', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('level')->options(self::getLevelOptions()),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('department.name')->label('Department')->placeholder('—'),
                Tables\Columns\TextColumn::make('level')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Executive' => 'danger',
                        'Management' => 'warning',
                        'Senior' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('staff_count')->counts('staff')->label('Staff'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')->options(self::getLevelOptions()),
                Tables\Filters\SelectFilter::make('department_id')->label('Department')->relationship('department', 'name'),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['department']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPositions::route('/'),
            'create' => Pages\CreatePosition::route('/create'),
            'edit' => Pages\EditPosition::route('/{record}/edit'),
        ];
    }
}
