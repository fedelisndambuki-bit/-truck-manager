<?php

namespace App\Filament\Widgets\UserManagement;

use App\Models\SystemLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentUserActivity extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?string $heading = 'Recent User Activity';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $query = SystemLog::query()->latest();

        if ($companyId && !$user->hasRole('Super Admin')) {
            $query->where('company_id', $companyId);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->placeholder('System'),
                Tables\Columns\TextColumn::make('action')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(100),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(5);
    }
}
