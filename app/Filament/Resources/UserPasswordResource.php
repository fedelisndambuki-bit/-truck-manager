<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserPasswordResource\Pages;
use App\Models\User;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Traits\HasModuleAccess;

class UserPasswordResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'user-management';
    }

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'User Password';

    protected static ?string $modelLabel = 'User Password';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Selection')
                    ->description('Select an employee to create or manage their user account.')
                    ->visible(fn ($context) => $context === 'create')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Select Employee (Email)')
                            ->options(function() {
                                $user = auth()->user();
                                $query = Employee::query();
                                if ($user) {
                                    $query->where('company_id', $user->company_id);
                                }
                                return $query->pluck('email', 'id');
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) return;
                                $employee = Employee::find($state);
                                if ($employee) {
                                    $set('name', $employee->full_name);
                                    $set('email', $employee->email);
                                    $set('company_id', $employee->company_id);
                                }
                            })
                            ->dehydrated(false),

                        Forms\Components\Select::make('user_name_select')
                            ->label('Select User/Employee (Name)')
                            ->options(function() {
                                $user = auth()->user();
                                $query = Employee::query();
                                if ($user) {
                                    $query->where('company_id', $user->company_id);
                                }
                                return $query->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) return;
                                $employee = Employee::find($state);
                                if ($employee) {
                                    $set('name', $employee->full_name);
                                    $set('email', $employee->email);
                                    $set('employee_id', $employee->id);
                                }
                            })
                            ->dehydrated(false),
                    ])->columns(2),

                Forms\Components\Section::make('Account Credentials')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('User Name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label(fn ($context) => $context === 'create' ? 'Create Password' : 'Edit Password')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->rule(Password::min(8)->mixedCase()->numbers())
                            ->helperText('Rules: Use at least 8 characters, include upper and lower case letters, and at least one number.')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state)),

                        Forms\Components\Select::make('status')
                            ->label('Password Status')
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Blocked',
                                'dormant' => 'Inactive',
                            ])
                            ->required()
                            ->default('active')
                            ->native(false),
                        
                        Forms\Components\Hidden::make('company_id')
                            ->default(fn() => auth()->user()?->company_id)
                            ->dehydrated(fn($state) => filled($state)),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('User Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'suspended',
                        'warning' => 'dormant',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Active',
                        'suspended' => 'Blocked',
                        'dormant' => 'Inactive',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Password Update')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Blocked',
                        'dormant' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit Password'),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete Password'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserPasswords::route('/'),
            'create' => Pages\CreateUserPassword::route('/create'),
            'edit' => Pages\EditUserPassword::route('/{record}/edit'),
        ];
    }
}
