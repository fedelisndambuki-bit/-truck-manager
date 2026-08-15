<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Pages;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Models\Conveyor;
use App\Models\Crusher;
use App\Models\Feeder;
use App\Models\Maintenance;
use App\Models\Screen;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * A computed view over the real, existing Maintenance model (already used
 * by Preventive Maintenance / Breakdown Management app-wide) filtered to
 * equipment linked from Crushers/Screens/Conveyors/Feeders — not a new
 * maintenance table, so the existing maintenance workflow genuinely applies
 * to plant equipment.
 */
class PlantMaintenance extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'plant.view';
    }

    protected static ?string $cluster = CrushingPlantManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Maintenance';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Plant Equipment Maintenance';

    protected static string $view = 'filament.clusters.crushing-plant-management.pages.plant-maintenance';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75" />',
            'title' => 'Maintenance',
            'subtitle' => 'Real maintenance records for equipment linked to crushers, screens, conveyors and feeders.',
        ]);
    }

    protected function plantEquipmentIds(?int $companyId): array
    {
        $scope = fn ($q) => $q->when($companyId, fn ($qq) => $qq->where('company_id', $companyId));

        return collect()
            ->merge(Crusher::query()->tap($scope)->whereNotNull('equipment_id')->pluck('equipment_id'))
            ->merge(Screen::query()->tap($scope)->whereNotNull('equipment_id')->pluck('equipment_id'))
            ->merge(Conveyor::query()->tap($scope)->whereNotNull('equipment_id')->pluck('equipment_id'))
            ->merge(Feeder::query()->tap($scope)->whereNotNull('equipment_id')->pluck('equipment_id'))
            ->unique()
            ->values()
            ->all();
    }

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;
        $equipmentIds = $this->plantEquipmentIds($companyId);

        return $table
            ->query(
                Maintenance::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereIn('equipment_id', $equipmentIds)
                    ->with('equipment')
            )
            ->columns([
                Tables\Columns\TextColumn::make('equipment.name')->label('Equipment'),
                Tables\Columns\TextColumn::make('maintenance_type')->badge(),
                Tables\Columns\TextColumn::make('description')->limit(50)->placeholder('—'),
                Tables\Columns\TextColumn::make('maintenance_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('next_maintenance_date')->date()->placeholder('—'),
                Tables\Columns\TextColumn::make('cost')->money(fn () => session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->defaultSort('maintenance_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['Pending' => 'Pending', 'Completed' => 'Completed']),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Open in Maintenance')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Maintenance $record) => \App\Filament\Resources\MaintenanceResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->striped();
    }
}
