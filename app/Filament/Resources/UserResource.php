<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Employee;
use App\Models\ExternalOrganization;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use App\Traits\HasModuleAccess;

class UserResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'user-management';
    }

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->description('Select an existing driver to autofill details or enter them manually.')
                    ->schema([
                        Forms\Components\Select::make('driver_lookup_id')
                            ->label('Select Existing Driver')
                            ->options(fn () => Employee::query()->pluck('full_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $employee = Employee::find($state);
                                    if ($employee) {
                                        $set('name', $employee->full_name);
                                        $set('email', $employee->email);
                                        $set('phone', $employee->phone_number);
                                    }
                                }
                            })
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn (Get $get) => filled($get('driver_lookup_id')))
                            ->dehydrated(),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn (Get $get) => filled($get('driver_lookup_id')))
                            ->dehydrated(),

                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(255)
                            ->disabled(fn (Get $get) => filled($get('driver_lookup_id')))
                            ->dehydrated(),

                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name', function ($query) {
                                // Only Super Admins can assign the Super Admin role
                                if (! auth()->user()?->hasRole('Super Admin')) {
                                    $query->where('name', '!=', 'Super Admin');
                                }
                            })
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'dormant' => 'Dormant',
                                'on_leave' => 'On Leave',
                            ])
                            ->default('active')
                            ->native(false)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('User Type')
                    ->schema([
                        Forms\Components\Radio::make('user_type')
                            ->label('')
                            ->options([
                                'internal' => 'Internal User',
                                'external' => 'External User',
                            ])
                            ->descriptions([
                                'internal' => 'Staff member of your company.',
                                'external' => 'Client, consultant, architect, subcontractor, supplier, or other outside collaborator — restricted to their assigned project(s).',
                            ])
                            ->default('internal')
                            ->inline()
                            ->live()
                            ->required(),
                    ]),

                Forms\Components\Section::make('External Access')
                    ->description('Appears only for External users — controls which project this person can see and what they can do there.')
                    ->visible(fn (Get $get) => $get('user_type') === 'external')
                    ->schema([
                        Forms\Components\Select::make('external_organization_id')
                            ->label('Company')
                            ->options(fn () => ExternalOrganization::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\Select::make('type')->options([
                                    'Consultant' => 'Consultant', 'Architect' => 'Architect',
                                    'Structural Engineer' => 'Structural Engineer', 'MEP Engineer' => 'MEP Engineer',
                                    'Quantity Surveyor' => 'Quantity Surveyor', 'Clerk of Works' => 'Clerk of Works',
                                    'Supplier' => 'Supplier', 'Subcontractor' => 'Subcontractor',
                                    'Auditor' => 'Auditor', 'Other' => 'Other',
                                ])->default('Consultant')->required(),
                                Forms\Components\TextInput::make('contact_person'),
                                Forms\Components\TextInput::make('phone')->tel(),
                                Forms\Components\TextInput::make('email')->email(),
                            ]),
                        Forms\Components\Select::make('assignment_project_id')
                            ->label('Project')
                            ->options(fn () => Project::query()->pluck('name', 'id'))
                            ->searchable()
                            ->helperText('The first project this person is assigned to. Add more from the Project Assignments tab after saving.'),
                        Forms\Components\DatePicker::make('assignment_expiry_date')
                            ->label('Expiry Date')
                            ->helperText('Leave blank for no expiry.'),
                        Forms\Components\Fieldset::make('Capabilities')
                            ->schema([
                                Forms\Components\Toggle::make('assignment_can_view_drawings')->label('Can View Drawings')->default(true),
                                Forms\Components\Toggle::make('assignment_can_download_documents')->label('Can Download Documents')->default(true),
                                Forms\Components\Toggle::make('assignment_can_upload_documents')->label('Can Upload Documents')->default(false),
                                Forms\Components\Toggle::make('assignment_can_comment')->label('Can Comment')->default(true),
                                Forms\Components\Toggle::make('assignment_can_approve')->label('Can Approve')->default(false),
                                Forms\Components\Toggle::make('assignment_can_view_financials')->label('Can View Financials')->default(false),
                            ])->columns(3),
                    ])->columns(2),

                Forms\Components\Section::make('Security')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->confirmed()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->password()
                            ->visible(fn (string $context, Get $get): bool => $context === 'create' || filled($get('password')))
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'suspended',
                        'warning' => 'dormant',
                        'info' => 'on_leave',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'active',
                        'heroicon-o-x-circle' => 'suspended',
                        'heroicon-o-moon' => 'dormant',
                        'heroicon-o-arrow-right-on-rectangle' => 'on_leave',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Filter by Role'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'dormant' => 'Dormant',
                        'on_leave' => 'On Leave',
                    ])
                    ->label('Filter by Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalIcon('heroicon-o-eye')
                    ->modalIconColor('gray'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('link_driver')
                    ->label('Link Driver')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->modalHeading('Link Existing Driver')
                    ->form([
                        Forms\Components\Select::make('driver_id')
                            ->label('Select Driver')
                            ->options(function () {
                                $drivers = \App\Models\Driver::whereNull('user_id')
                                    ->join('employees', 'drivers.license_number', '=', 'employees.license_number')
                                    ->select('drivers.id', 'employees.full_name')
                                    ->get();

                                return $drivers->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->required(),
                    ])

                    ->requiresConfirmation()
                    ->action(function ($record, array $data) {
                        \Illuminate\Support\Facades\Log::info('LinkDriver action triggered for User: ' . $record->id);
                        $driver = \App\Models\Driver::find($data['driver_id']);
                        if (!$driver) return;

                        $driver->update(['user_id' => $record->id]);
                        if (!$record->hasRole('Driver')) {
                            $record->assignRole('Driver');
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Driver linked successfully')
                            ->success()
                            ->send();
                    }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['roles']);
        $user = auth()->user();

        if ($user) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProjectAssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
