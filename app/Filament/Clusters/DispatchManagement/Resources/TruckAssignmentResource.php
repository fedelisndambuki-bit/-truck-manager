<?php

namespace App\Filament\Clusters\DispatchManagement\Resources;

use App\Filament\Clusters\DispatchManagement;
use App\Filament\Clusters\DispatchManagement\Resources\TruckAssignmentResource\Pages;
use App\Filament\Resources\TruckResource;
use App\Models\Truck;
use App\Services\Quarry\DispatchSchedulingService;
use App\Traits\HasModuleAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Truck Assignments" — a real fitness-check view over the existing fleet
 * Truck records (Truck CRUD itself already lives in TruckResource; this is
 * a dispatch-scoped read view showing whether each truck is currently fit
 * to be assigned a dispatch, per DispatchSchedulingService's real checks).
 */
class TruckAssignmentResource extends Resource
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

    protected static ?string $model = Truck::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Truck Assignments';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        $service = app(DispatchSchedulingService::class);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plate_number')->label('Plate Number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('truck_type'),
                Tables\Columns\TextColumn::make('capacity_tons')->suffix(' t'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\IconColumn::make('fit_available')->label('Available')->boolean()
                    ->state(fn (Truck $record) => $service->truckFitnessCheck($record)['available']),
                Tables\Columns\IconColumn::make('fit_insurance')->label('Insurance')->boolean()
                    ->state(fn (Truck $record) => $service->truckFitnessCheck($record)['insurance_valid']),
                Tables\Columns\IconColumn::make('fit_license')->label('Road License')->boolean()
                    ->state(fn (Truck $record) => $service->truckFitnessCheck($record)['road_license_valid']),
                Tables\Columns\IconColumn::make('fit_load')->label('Not Overloaded')->boolean()
                    ->state(fn (Truck $record) => $service->truckFitnessCheck($record)['not_overloaded']),
                Tables\Columns\TextColumn::make('trips_today')->label('Trips Today')
                    ->state(fn (Truck $record) => $service->truckTripsToday($record)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['Available' => 'Available', 'In Transit' => 'In Transit', 'Under Maintenance' => 'Under Maintenance']),
            ])
            ->actions([
                Tables\Actions\Action::make('editInFleet')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (Truck $record) => TruckResource::getUrl('edit', ['record' => $record])),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTruckAssignments::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
