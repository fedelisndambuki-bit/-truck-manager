<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabourerResource\Pages;
use App\Models\Labourer;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LabourerResource extends Resource
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

    protected static ?string $model = Labourer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Project Assignments';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 14;

    public static function getRoleOptions(): array
    {
        return [
            'Foreman' => 'Foreman',
            'Engineer' => 'Engineer',
            'Contractor' => 'Contractor',
            'General Worker' => 'General Worker',
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved' => 'success',
            'Rejected' => 'danger',
            default => 'warning',
        };
    }

    public static function selectEmployeeSchema(): array
    {
        return [
            Forms\Components\Select::make('employee_id')
                ->label('Employee')
                ->relationship('employee', 'full_name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('role_on_site')
                ->label('Role')
                ->options(self::getRoleOptions())
                ->default('General Worker')
                ->required(),
        ];
    }

    public static function assignmentSchema(): array
    {
        return [
            Forms\Components\Select::make('project_id')
                ->label('Project')
                ->relationship('project', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('site_id')
                ->label('Site')
                ->relationship('site', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('department_id')
                ->label('Department')
                ->relationship('department', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('supervisor_id')
                ->label('Supervisor')
                ->relationship('supervisor', 'full_name')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('construction_phase'),
            Forms\Components\Select::make('shift_id')
                ->label('Shift')
                ->relationship('shift', 'name')
                ->searchable()
                ->preload(),
        ];
    }

    public static function scheduleSchema(): array
    {
        return [
            Forms\Components\DatePicker::make('expected_end_date'),
            Forms\Components\TextInput::make('daily_rate')
                ->numeric()
                ->prefix(session('currency', 'KES')),
        ];
    }

    public static function approvalSchema(): array
    {
        return [
            Forms\Components\Select::make('approval_status')
                ->label('Approval Status')
                ->options(['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'])
                ->default('Pending')
                ->required(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assignment Details')->schema([
                    ...self::selectEmployeeSchema(),
                    ...self::assignmentSchema(),
                    ...self::scheduleSchema(),
                    ...self::approvalSchema(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')->label('Employee')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->toggleable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site'),
                Tables\Columns\TextColumn::make('role_on_site')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Foreman' => 'warning',
                        'Engineer' => 'info',
                        'Contractor' => 'gray',
                        'General Worker' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('supervisor.full_name')->label('Supervisor')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('shift.name')->label('Shift')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('daily_rate')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('approval_status')->label('Status')->badge()->color(fn (string $state) => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_on_site')->label('Role')->options(self::getRoleOptions()),
                Tables\Filters\SelectFilter::make('approval_status')->label('Status')->options([
                    'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                ]),
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Labourer $record) => $record->update([
                        'approval_status' => 'Approved', 'approved_by_id' => Auth::id(), 'approved_at' => now(),
                    ]))
                    ->visible(fn (Labourer $record) => $record->approval_status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('hr.approve'))),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLabourers::route('/'),
            'create' => Pages\CreateLabourer::route('/create'),
            'edit' => Pages\EditLabourer::route('/{record}/edit'),
        ];
    }
}
