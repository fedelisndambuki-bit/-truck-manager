<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ActiveProjectsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $companyId = auth()->user()?->company_id;

        return $table
            ->query(
                Project::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->where('status', 'Active')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client'),
                Tables\Columns\TextColumn::make('site_address'),
                Tables\Columns\TextColumn::make('end_date')->date()->label('Deadline'),
            ]);
    }
}
