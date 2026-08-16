<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingRecordResource\Pages;
use App\Models\TrainingRecord;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrainingRecordResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'hr.training_management';
    }

    protected static ?string $model = TrainingRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Training';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 11;

    public static function getTypeOptions(): array
    {
        return [
            'Safety Induction' => 'Safety Induction',
            'Equipment Training' => 'Equipment Training',
            'Technical' => 'Technical',
            'First Aid' => 'First Aid',
            'Fire Safety' => 'Fire Safety',
            'Toolbox Talk' => 'Toolbox Talk',
            'Refresher' => 'Refresher',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Training Record')->schema([
                    Forms\Components\Select::make('staff_id')
                        ->label('Employee')
                        ->relationship('staff', 'full_name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('training_type')->options(self::getTypeOptions())->required(),
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
                Tables\Columns\TextColumn::make('training_type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('title')->placeholder('—'),
                Tables\Columns\TextColumn::make('provider')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')->date()->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Completed' => 'success',
                    'Scheduled' => 'info',
                    'Expired' => 'danger',
                    default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('training_type')->label('Type')->options(self::getTypeOptions()),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['staff']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingRecords::route('/'),
            'create' => Pages\CreateTrainingRecord::route('/create'),
            'edit' => Pages\EditTrainingRecord::route('/{record}/edit'),
        ];
    }
}
