<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Pages;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\MaintenanceWorkOrderResource;
use App\Filament\Clusters\EquipmentAssetManagement\Widgets\WorkshopCapacityWidget;
use App\Models\MaintenanceWorkOrder;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Workshop capacity + live jobs queue. Bays and Workshops themselves are
 * managed via WorkshopResource/WorkshopBayResource (plain CRUD); this page
 * is the operational view of what's currently in the workshop.
 */
class WorkshopPage extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'eam.view';
    }

    protected static ?string $cluster = EquipmentAssetManagement::class;

    protected static ?string $slug = 'workshop';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Workshop';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Workshop';

    protected static string $view = 'filament.clusters.equipment-asset-management.pages.workshop';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75" />',
            'title' => 'Workshop',
            'subtitle' => 'Bay capacity and the live maintenance jobs queue.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            WorkshopCapacityWidget::class,
        ];
    }

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->query(
                MaintenanceWorkOrder::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereNotIn('status', ['Closed'])
                    ->with(['equipment', 'technician', 'workshop', 'workshopBay'])
            )
            ->heading('Jobs Queue')
            ->columns([
                Tables\Columns\TextColumn::make('work_order_number')->label('WO#')->searchable(),
                Tables\Columns\TextColumn::make('equipment.name')->label('Equipment'),
                Tables\Columns\TextColumn::make('workshop.name')->label('Workshop')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('workshopBay.bay_number')->label('Bay')->placeholder('—'),
                Tables\Columns\TextColumn::make('technician.full_name')->label('Technician')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('priority')->badge()->color(fn (string $state): string => MaintenanceWorkOrderResource::getPriorityColor($state)),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => MaintenanceWorkOrderResource::getStatusColor($state)),
            ])
            ->defaultSort('created_at')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(MaintenanceWorkOrderResource::getStatusOptions()),
                Tables\Filters\SelectFilter::make('workshop_id')->label('Workshop')->relationship('workshop', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (MaintenanceWorkOrder $record) => MaintenanceWorkOrderResource::getUrl('edit', ['record' => $record])),
            ])
            ->striped();
    }
}
