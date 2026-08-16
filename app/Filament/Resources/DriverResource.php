<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverResource\Pages;
use App\Models\Driver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Traits\HasModuleAccess;

class DriverResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'driver-management';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }

    protected static ?string $model = Driver::class;

    protected static ?string $navigationLabel = 'Driver Accounts';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Fleet Management';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['user', 'employee']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Link')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Login Account')
                            ->relationship('user', 'name', fn ($query) => $query->where('company_id', auth()->user()->company_id))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('employee_id')
                            ->label('Linked Employee')
                            ->relationship('employee', 'full_name', fn ($query) => $query->where('company_id', auth()->user()->company_id))
                            ->searchable()
                            ->preload()
                            ->helperText('Bridges this driver account to its HR employee record (commission postings, wallet crediting, and profile details all resolve through this link).'),
                    ])->columns(2),

                Forms\Components\Section::make('Driver Profile')
                    ->schema([
                        Forms\Components\TextInput::make('license_number'),
                        Forms\Components\Select::make('status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                            ->default('active')
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('commission_rate')
                            ->numeric()
                            ->suffix('%')
                            ->default(10)
                            ->helperText('Percentage of trip revenue credited to this driver\'s wallet on trip completion.'),
                        Forms\Components\TextInput::make('risk_score')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('performance_score')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Driver')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Login Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Linked Employee')
                    ->placeholder('— not linked —')
                    ->searchable(),
                Tables\Columns\TextColumn::make('license_number')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('risk_score')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('performance_score')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Provisioned')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive']),
                Tables\Filters\Filter::make('unlinked')
                    ->label('Not linked to an employee')
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereNull('employee_id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrivers::route('/'),
            'create' => Pages\CreateDriver::route('/create'),
            'edit' => Pages\EditDriver::route('/{record}/edit'),
        ];
    }
}
