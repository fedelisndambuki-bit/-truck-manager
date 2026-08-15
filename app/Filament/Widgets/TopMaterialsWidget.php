<?php

namespace App\Filament\Widgets;

use App\Models\Material;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class TopMaterialsWidget extends BaseWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Top Used Materials';

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->query(
                Material::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->withSum('deliveries as total_delivered', 'quantity')
                    ->orderByDesc('total_delivered')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('unit'),
                Tables\Columns\TextColumn::make('total_delivered')->label('Total Delivered')->default(0),
                Tables\Columns\TextColumn::make('quantity_on_hand')->label('On Hand'),
            ]);
    }
}
