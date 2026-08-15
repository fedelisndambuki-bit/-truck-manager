<?php

namespace App\Filament\Clusters\DispatchManagement\Resources;

use App\Filament\Clusters\DispatchManagement;
use App\Filament\Clusters\DispatchManagement\Resources\DriverAssignmentResource\Pages;
use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\Quarry\DispatchSchedulingService;
use App\Traits\HasModuleAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Driver Assignments" — a real fitness-check view over the existing
 * Employee records (Employee CRUD itself lives in EmployeeResource). Only
 * license_expiry is checked, since that's the only fitness-relevant column
 * Employee actually tracks — no fabricated medical/training/fatigue checks.
 */
class DriverAssignmentResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'dispatch.view';
    }

    protected static ?string $cluster = DispatchManagement::class;

    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Driver Assignments';

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        $service = app(DispatchSchedulingService::class);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label('Driver')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('license_number')->placeholder('—'),
                Tables\Columns\TextColumn::make('license_expiry')->date('d M Y')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\IconColumn::make('fit_active')->label('Active')->boolean()
                    ->state(fn (Employee $record) => $service->driverFitnessCheck($record)['active']),
                Tables\Columns\IconColumn::make('fit_license')->label('License Valid')->boolean()
                    ->state(fn (Employee $record) => $service->driverFitnessCheck($record)['license_valid']),
                Tables\Columns\IconColumn::make('fit_load')->label('Not Overloaded')->boolean()
                    ->state(fn (Employee $record) => $service->driverFitnessCheck($record)['not_overloaded']),
                Tables\Columns\TextColumn::make('trips_today')->label('Trips Today')
                    ->state(fn (Employee $record) => $service->driverTripsToday($record)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['Active' => 'Active', 'On Leave' => 'On Leave', 'Suspended' => 'Suspended']),
            ])
            ->actions([
                Tables\Actions\Action::make('editInHr')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (Employee $record) => EmployeeResource::getUrl('edit', ['record' => $record])),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverAssignments::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
