<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Models\Staff;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StaffResource extends Resource
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

    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Employees';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getEmploymentTypeOptions(): array
    {
        return [
            'Permanent' => 'Permanent',
            'Contract' => 'Contract',
            'Casual' => 'Casual',
            'Probation' => 'Probation',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'Active' => 'Active',
            'On Leave' => 'On Leave',
            'Suspended' => 'Suspended',
            'Terminated' => 'Terminated',
            'Probation' => 'Probation',
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Active' => 'success',
            'On Leave' => 'warning',
            'Probation' => 'info',
            'Suspended' => 'danger',
            'Terminated' => 'gray',
            default => 'gray',
        };
    }

    public static function personalInfoSchema(): array
    {
        return [
            Forms\Components\FileUpload::make('photo')
                ->image()
                ->disk('public')
                ->directory('staff-photos')
                ->avatar()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('full_name')->required(),
            Forms\Components\Select::make('gender')->options([
                'Male' => 'Male',
                'Female' => 'Female',
            ]),
            Forms\Components\DatePicker::make('date_of_birth'),
            Forms\Components\Select::make('marital_status')->options([
                'Single' => 'Single',
                'Married' => 'Married',
                'Divorced' => 'Divorced',
                'Widowed' => 'Widowed',
            ]),
            Forms\Components\TextInput::make('national_id'),
            Forms\Components\TextInput::make('passport_number'),
            Forms\Components\TextInput::make('phone_number')->tel(),
            Forms\Components\TextInput::make('email')->email(),
            Forms\Components\Select::make('county')->searchable()->options(array_combine(self::kenyanCounties(), self::kenyanCounties())),
            Forms\Components\Textarea::make('physical_address')->columnSpanFull(),
        ];
    }

    public static function kenyanCounties(): array
    {
        return [
            'Nairobi', 'Mombasa', 'Kwale', 'Kilifi', 'Tana River', 'Lamu', 'Taita-Taveta',
            'Garissa', 'Wajir', 'Mandera', 'Marsabit', 'Isiolo', 'Meru', 'Tharaka-Nithi',
            'Embu', 'Kitui', 'Machakos', 'Makueni', 'Nyandarua', 'Nyeri', 'Kirinyaga',
            'Murang\'a', 'Kiambu', 'Turkana', 'West Pokot', 'Samburu', 'Trans Nzoia',
            'Uasin Gishu', 'Elgeyo-Marakwet', 'Nandi', 'Baringo', 'Laikipia', 'Nakuru',
            'Narok', 'Kajiado', 'Kericho', 'Bomet', 'Kakamega', 'Vihiga', 'Bungoma',
            'Busia', 'Siaya', 'Kisumu', 'Homa Bay', 'Migori', 'Kisii', 'Nyamira',
        ];
    }

    public static function employmentSchema(): array
    {
        return [
            Forms\Components\Select::make('employment_type')
                ->options(self::getEmploymentTypeOptions())
                ->default('Permanent')
                ->required(),
            Forms\Components\Select::make('status')
                ->options(self::getStatusOptions())
                ->default('Active')
                ->required(),
            Forms\Components\Select::make('department_id')
                ->label('Department')
                ->relationship('department', 'name')
                ->searchable()
                ->preload()
                ->createOptionForm([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('code'),
                ]),
            Forms\Components\Select::make('position_id')
                ->label('Position')
                ->relationship('position', 'title')
                ->searchable()
                ->preload()
                ->createOptionForm([
                    Forms\Components\TextInput::make('title')->required(),
                ]),
            Forms\Components\Select::make('supervisor_id')
                ->label('Supervisor')
                ->relationship('supervisor', 'full_name')
                ->searchable()
                ->preload(),
            Forms\Components\DatePicker::make('date_hired'),
            Forms\Components\DatePicker::make('contract_expiry')
                ->visible(fn (Forms\Get $get) => $get('employment_type') === 'Contract'),
        ];
    }

    public static function statutorySchema(): array
    {
        return [
            Forms\Components\TextInput::make('kra_pin')->label('KRA PIN'),
            Forms\Components\TextInput::make('nssf_number')->label('NSSF Number'),
            Forms\Components\TextInput::make('sha_number')->label('SHA Number'),
            Forms\Components\TextInput::make('bank_name'),
            Forms\Components\TextInput::make('bank_account_number'),
            Forms\Components\TextInput::make('bank_branch'),
        ];
    }

    public static function compensationSchema(): array
    {
        return [
            Forms\Components\TextInput::make('basic_salary')
                ->numeric()
                ->prefix(session('currency', 'KES')),
            Forms\Components\TextInput::make('allowances')
                ->numeric()
                ->default(0)
                ->prefix(session('currency', 'KES')),
        ];
    }

    public static function emergencyMedicalSchema(): array
    {
        return [
            Forms\Components\TextInput::make('emergency_contact_name'),
            Forms\Components\TextInput::make('emergency_contact_phone'),
            Forms\Components\TextInput::make('next_of_kin_name'),
            Forms\Components\TextInput::make('next_of_kin_relationship'),
            Forms\Components\TextInput::make('next_of_kin_phone'),
            Forms\Components\Textarea::make('medical_info')
                ->label('Medical Information')
                ->helperText('Blood group, allergies, conditions')
                ->columnSpanFull(),
            Forms\Components\KeyValue::make('ppe_sizes')
                ->label('PPE Sizes')
                ->keyLabel('Item')
                ->valueLabel('Size')
                ->columnSpanFull(),
        ];
    }

    public static function notesSchema(): array
    {
        return [
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Staff')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Personal Info')
                            ->schema(self::personalInfoSchema())
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make('Employment')
                            ->schema(self::employmentSchema())
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make('Statutory & Bank')
                            ->schema(self::statutorySchema())
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make('Compensation')
                            ->schema(self::compensationSchema())
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make('Emergency & Medical')
                            ->schema(self::emergencyMedicalSchema())
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make('Notes')
                            ->schema(self::notesSchema()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')->circular()->label(''),
                Tables\Columns\TextColumn::make('staff_number')->label('Staff No.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('full_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('department.name')->label('Department')->toggleable(),
                Tables\Columns\TextColumn::make('position.title')->label('Position')->toggleable(),
                Tables\Columns\TextColumn::make('employment_type')->badge()->color(fn (string $state) => match ($state) {
                    'Permanent' => 'success',
                    'Contract' => 'info',
                    'Casual' => 'gray',
                    'Probation' => 'warning',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('phone_number')->label('Phone')->toggleable(),
                Tables\Columns\TextColumn::make('date_hired')->date()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employment_type')->options(self::getEmploymentTypeOptions()),
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('department_id')->label('Department')->relationship('department', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('full_name')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
            'view' => Pages\ViewStaff::route('/{record}'),
        ];
    }
}
