<?php

namespace App\Filament\Widgets\Quarry;

use App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource;
use App\Models\CrushingPlant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class CrushingPlantStatusWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Crushing Plant Status';

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->query(
                CrushingPlant::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->with('extractionSite')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Site'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => CrushingPlantResource::getStatusColor($state)),
                Tables\Columns\TextColumn::make('capacity_tph')->label('Capacity')->suffix(' t/h')->placeholder('—'),
            ])
            ->paginated(false);
    }
}
