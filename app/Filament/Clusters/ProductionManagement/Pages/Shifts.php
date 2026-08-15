<?php

namespace App\Filament\Clusters\ProductionManagement\Pages;

use App\Filament\Clusters\ProductionManagement;
use App\Models\ProductionLog;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Shift-based production performance — built over the existing ProductionLog
 * (shift_id, supervisor, quantity, downtime) rather than a second CRUD for
 * shift definitions; the existing ShiftResource under Labour & HR Management
 * remains the single place to manage Shift types.
 */
class Shifts extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'production.view';
    }

    protected static ?string $cluster = ProductionManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Shifts';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Shift Performance';

    protected static string $view = 'filament.clusters.production-management.pages.shifts';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
            'title' => 'Shift Performance',
            'subtitle' => 'Production by shift, supervisor and downtime — shift definitions are managed under Labour & HR Management.',
        ]);
    }

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->query(
                ProductionLog::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->with(['shift', 'supervisor', 'extractionSite'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('shift.name')->label('Shift')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry'),
                Tables\Columns\TextColumn::make('supervisor.full_name')->label('Supervisor')->placeholder('—'),
                Tables\Columns\TextColumn::make('quantity_tonnes')->label('Actual Production')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('downtime_minutes')->label('Downtime')->numeric()->suffix(' min'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('shift_id')->label('Shift')->relationship('shift', 'name'),
            ])
            ->striped();
    }
}
