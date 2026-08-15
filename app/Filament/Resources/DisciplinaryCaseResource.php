<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisciplinaryCaseResource\Pages;
use App\Models\DisciplinaryCase;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DisciplinaryCaseResource extends Resource
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

    protected static ?string $model = DisciplinaryCase::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Disciplinary Cases';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static bool $shouldRegisterNavigation = false;

    public static function getTypeOptions(): array
    {
        return [
            'Verbal Warning' => 'Verbal Warning',
            'Written Warning' => 'Written Warning',
            'Suspension' => 'Suspension',
            'Performance Improvement Plan' => 'Performance Improvement Plan',
            'Termination' => 'Termination',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Disciplinary Case')->schema([
                    Forms\Components\Select::make('staff_id')
                        ->label('Employee')
                        ->relationship('staff', 'full_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('type')->options(self::getTypeOptions())->required(),
                    Forms\Components\DatePicker::make('date')->default(now())->required(),
                    Forms\Components\Select::make('issued_by_id')
                        ->label('Issued By')
                        ->relationship('issuedBy', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')->options(['Open' => 'Open', 'Resolved' => 'Resolved'])->default('Open')->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.full_name')->label('Employee')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge()->color(fn (string $state) => match ($state) {
                    'Termination' => 'danger',
                    'Suspension' => 'danger',
                    'Performance Improvement Plan' => 'warning',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('date')->date(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Open' ? 'warning' : 'success'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisciplinaryCases::route('/'),
            'create' => Pages\CreateDisciplinaryCase::route('/create'),
            'edit' => Pages\EditDisciplinaryCase::route('/{record}/edit'),
        ];
    }
}
