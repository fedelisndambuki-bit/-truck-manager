<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SafetyTrainingResource\Pages;
use App\Models\TrainingRecord;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SafetyTrainingResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'safety.view';
    }

    protected static ?string $model = TrainingRecord::class;

    protected static ?string $slug = 'safety-training';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Training';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 130;

    public const SAFETY_TYPES = [
        'Safety Induction', 'Working at Height', 'Fire Safety', 'First Aid', 'Confined Space',
        'Scaffold Safety', 'Crane Safety', 'Equipment Safety', 'Electrical Safety',
    ];

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('training_type', self::SAFETY_TYPES);
    }

    public static function getTypeOptions(): array
    {
        return array_combine(self::SAFETY_TYPES, self::SAFETY_TYPES);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Safety Training Record')->schema([
                    Forms\Components\Select::make('staff_id')
                        ->label('Employee')
                        ->relationship('staff', 'full_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('training_type')->label('Course')->options(self::getTypeOptions())->required(),
                    Forms\Components\TextInput::make('title'),
                    Forms\Components\TextInput::make('provider'),
                    Forms\Components\DatePicker::make('date')->default(now())->required(),
                    Forms\Components\DatePicker::make('expiry_date'),
                    Forms\Components\Select::make('status')->options([
                        'Scheduled' => 'Scheduled', 'Completed' => 'Completed', 'Expired' => 'Expired',
                    ])->default('Completed')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.full_name')->label('Employee')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('training_type')->label('Course')->badge(),
                Tables\Columns\TextColumn::make('provider')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')->date()->placeholder('—')->sortable()
                    ->color(fn ($record) => $record->expiry_date && $record->expiry_date->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Completed' => 'success', 'Scheduled' => 'info', 'Expired' => 'danger', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('training_type')->label('Course')->options(self::getTypeOptions()),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Scheduled' => 'Scheduled', 'Completed' => 'Completed', 'Expired' => 'Expired',
                ]),
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
            'index' => Pages\ListSafetyTrainingRecords::route('/'),
            'create' => Pages\CreateSafetyTrainingRecord::route('/create'),
            'edit' => Pages\EditSafetyTrainingRecord::route('/{record}/edit'),
        ];
    }
}
