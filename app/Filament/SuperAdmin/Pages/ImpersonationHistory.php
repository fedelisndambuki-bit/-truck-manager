<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\SystemLog;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ImpersonationHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Impersonation History';

    protected static ?string $navigationGroup = 'Security';

    protected static ?int $navigationSort = 70;

    protected static string $view = 'filament.super-admin.pages.impersonation-history';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SystemLog::query()
                    ->whereIn('action', ['admin_impersonation_started', 'admin_impersonation_stopped'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('company.name')->label('Company')->placeholder('Platform'),
                Tables\Columns\BadgeColumn::make('action')->colors([
                    'warning' => 'admin_impersonation_started',
                    'success' => 'admin_impersonation_stopped',
                ]),
                Tables\Columns\TextColumn::make('description')->wrap()->limit(120),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
