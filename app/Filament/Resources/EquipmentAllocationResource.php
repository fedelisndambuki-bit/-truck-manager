<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EquipmentAllocationResource\Pages;
use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class EquipmentAllocationResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    protected static ?string $model = EquipmentAllocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Equipment Allocation';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 5;

    public static function getPriorityOptions(): array
    {
        return ['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Critical' => 'Critical'];
    }

    public static function getAllocationTypeOptions(): array
    {
        return ['Temporary' => 'Temporary', 'Long Term' => 'Long Term', 'Emergency' => 'Emergency'];
    }

    public static function getApprovalStatusOptions(): array
    {
        return ['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'];
    }

    public static function selectEquipmentSchema(): array
    {
        return [
            Forms\Components\Select::make('equipment_id')
                ->label('Equipment')
                ->relationship('equipment', 'name')
                ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code}) — {$record->status}")
                ->searchable()
                ->preload()
                ->required()
                ->columnSpanFull(),
        ];
    }

    public static function assignProjectSchema(): array
    {
        return [
            Forms\Components\Select::make('project_id')
                ->label('Project')
                ->relationship('project', 'name')
                ->searchable()
                ->preload()
                ->live(),
            Forms\Components\Select::make('site_id')
                ->label('Site')
                ->relationship('site', 'name', fn ($query, $get) => $get('project_id') ? $query->where('project_id', $get('project_id')) : $query)
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('construction_phase'),
            Forms\Components\TextInput::make('department'),
            Forms\Components\Select::make('priority')->options(self::getPriorityOptions())->default('Medium'),
            Forms\Components\Select::make('allocation_type')->options(self::getAllocationTypeOptions())->default('Temporary'),
        ];
    }

    public static function assignOperatorSchema(): array
    {
        return [
            Forms\Components\Select::make('operator_employee_id')
                ->label('Operator')
                ->relationship('operator', 'full_name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('backup_operator_employee_id')
                ->label('Backup Operator')
                ->relationship('backupOperator', 'full_name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('supervisor_employee_id')
                ->label('Supervisor')
                ->relationship('supervisor', 'full_name')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('shift')->placeholder('e.g. Day Shift'),
            Forms\Components\DatePicker::make('allocated_from')->label('Start Date')->default(now()),
            Forms\Components\DatePicker::make('allocated_to')->label('Expected Return Date'),
        ];
    }

    public static function operationalInfoSchema(): array
    {
        return [
            Forms\Components\TextInput::make('purpose'),
            Forms\Components\TextInput::make('expected_daily_hours')->numeric()->suffix('hrs'),
            Forms\Components\TextInput::make('expected_fuel_usage')->numeric()->suffix('L'),
            Forms\Components\TextInput::make('expected_production'),
            Forms\Components\TextInput::make('working_zone'),
            Forms\Components\Select::make('risk_level')->options(self::getPriorityOptions()),
            Forms\Components\Textarea::make('special_instructions')->columnSpanFull(),
        ];
    }

    public static function approvalSchema(): array
    {
        return [
            Forms\Components\Placeholder::make('review')
                ->label('Review')
                ->content('Confirm the equipment, project/site, and operator details on the previous steps before submitting for approval.')
                ->columnSpanFull(),
            Forms\Components\Select::make('approval_status')
                ->options(self::getApprovalStatusOptions())
                ->default('Pending')
                ->disabled(fn () => ! (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('equipment.approve')))
                ->required(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Allocation')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Equipment')->schema(self::selectEquipmentSchema()),
                        Forms\Components\Tabs\Tab::make('Project')->schema(self::assignProjectSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Operator')->schema(self::assignOperatorSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Operations')->schema(self::operationalInfoSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Approval')->schema(self::approvalSchema()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['equipment', 'project', 'site', 'operator']))
            ->columns([
                Tables\Columns\TextColumn::make('equipment.equipment_code')->label('Equipment ID')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.name')->label('Equipment Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.equipment_type')->label('Category')->toggleable(),
                Tables\Columns\TextColumn::make('equipment.registration_number')->label('Registration')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->toggleable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->toggleable(),
                Tables\Columns\TextColumn::make('operator.full_name')->label('Operator')->toggleable(),
                Tables\Columns\TextColumn::make('equipment.status')
                    ->label('Current Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Available' => 'success',
                        'In Use' => 'info',
                        'Under Maintenance' => 'warning',
                        'Out of Service' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('operating_hours_today')->label('Operating Hours')->toggleable(),
                Tables\Columns\TextColumn::make('fuel_consumed_today')->label('Fuel Used')->toggleable(),
                Tables\Columns\TextColumn::make('allocated_from')->date()->label('Allocation Date')->toggleable(),
                Tables\Columns\TextColumn::make('allocated_to')->date()->label('Expected Return')->toggleable(),
                Tables\Columns\TextColumn::make('site.name')->label('Current Location')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approval_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'warning',
                    })
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')->options(self::getApprovalStatusOptions()),
                Tables\Filters\SelectFilter::make('equipment.equipment_type')
                    ->label('Category')
                    ->relationship('equipment', 'equipment_type'),
            ])
            ->actions([
                Tables\Actions\Action::make('viewEquipment')
                    ->label('View Equipment')
                    ->icon('heroicon-o-eye')
                    ->url(fn (EquipmentAllocation $record) => \App\Filament\Resources\EquipmentResource::getUrl('view', ['record' => $record->equipment_id])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('exportSelectedCsv')
                        ->label('Export Selected (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('equipment.export'))
                        ->action(fn (Collection $records) => static::streamAllocationsCsv($records))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportAllCsv')
                    ->label('Export (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn () => Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('equipment.export'))
                    ->action(fn () => static::streamAllocationsCsv(
                        EquipmentAllocation::query()->when(Auth::user()?->company_id, fn ($q, $id) => $q->where('company_id', $id))->get()
                    )),
                Tables\Actions\Action::make('generateReport')
                    ->label('Generate Report')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('gray')
                    ->url(fn () => \App\Filament\Pages\ConstructionReports::getUrl()),
                Tables\Actions\CreateAction::make()->label('Allocate Equipment'),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc');
    }

    public static function streamAllocationsCsv(Collection $allocations)
    {
        $headers = ['Equipment ID', 'Equipment Name', 'Category', 'Project', 'Site', 'Operator', 'Status', 'Operating Hours', 'Fuel Used', 'Allocation Date', 'Expected Return'];

        return response()->streamDownload(function () use ($allocations, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($allocations as $a) {
                fputcsv($handle, [
                    $a->equipment?->equipment_code,
                    $a->equipment?->name,
                    $a->equipment?->equipment_type,
                    $a->project?->name,
                    $a->site?->name,
                    $a->operator?->full_name,
                    $a->equipment?->status,
                    $a->operating_hours_today,
                    $a->fuel_consumed_today,
                    $a->allocated_from?->format('Y-m-d'),
                    $a->allocated_to?->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, 'equipment-allocations-' . now()->format('Y-m-d') . '.csv');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipmentAllocations::route('/'),
            'create' => Pages\CreateEquipmentAllocation::route('/create'),
            'edit' => Pages\EditEquipmentAllocation::route('/{record}/edit'),
        ];
    }
}
