<?php

namespace App\Filament\Clusters\ProductionManagement\Pages;

use App\Filament\Clusters\ProductionManagement;
use App\Models\CrushingPlantLog;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Plant Output — a report view over the existing CrushingPlantLog (already
 * captures throughput, output, fuel, power, downtime for each plant/shift),
 * with yield % computed here rather than stored.
 */
class PlantOutput extends Page implements HasTable
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

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Plant Output';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Plant Output';

    protected static string $view = 'filament.clusters.production-management.pages.plant-output';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0H12" />',
            'title' => 'Plant Output',
            'subtitle' => 'Throughput, output, yield, fuel and power consumption by crushing plant.',
        ]);
    }

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->query(
                CrushingPlantLog::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->with('crushingPlant')
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('crushingPlant.name')->label('Plant'),
                Tables\Columns\TextColumn::make('shift')->badge(),
                Tables\Columns\TextColumn::make('throughput_tonnes')->label('Input (Throughput)')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('output_tonnes')->label('Output')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('yield')
                    ->label('Yield %')
                    ->state(fn (CrushingPlantLog $record): string => $record->throughput_tonnes > 0
                        ? round(($record->output_tonnes / $record->throughput_tonnes) * 100, 1) . '%'
                        : '—'),
                Tables\Columns\TextColumn::make('fuel_usage_litres')->label('Fuel')->numeric()->suffix(' L')->placeholder('—'),
                Tables\Columns\TextColumn::make('power_consumption_kwh')->label('Power')->numeric()->suffix(' kWh')->placeholder('—'),
                Tables\Columns\TextColumn::make('downtime_minutes')->label('Downtime')->numeric()->suffix(' min'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('crushing_plant_id')->label('Plant')->relationship('crushingPlant', 'name'),
            ])
            ->striped();
    }
}
