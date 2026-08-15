<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\SystemLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

use App\Filament\SuperAdmin\Resources\SystemLogResource;
use Filament\Tables\Actions\Action;

class RecentSystemLogs extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SystemLog::query()->latest()->limit(5)
            )
            ->headerActions([
                Action::make('view_all')
                    ->label('Manage All Logs')
                    ->url(fn (): string => SystemLogResource::getUrl('index'))
                    ->icon('heroicon-m-arrow-right-circle')
                    ->extraAttributes([
                        'class' => 'tm-manage-logs-btn',
                    ])
                    ->button(),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->placeholder('Platform')
                    ->size('sm'),
                Tables\Columns\TextColumn::make('action')
                    ->weight('bold')
                    ->size('sm'),
                Tables\Columns\BadgeColumn::make('severity')
                    ->colors([
                        'gray' => 'info',
                        'warning' => 'warning',
                        'danger' => 'error',
                        'success' => 'success',
                    ])
                    ->size('sm'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->size('sm'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->size('sm'),
            ])
            ->paginated(false);
    }
}
