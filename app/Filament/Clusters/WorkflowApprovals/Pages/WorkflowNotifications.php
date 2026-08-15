<?php

namespace App\Filament\Clusters\WorkflowApprovals\Pages;

use App\Filament\Clusters\WorkflowApprovals;
use App\Notifications\WorkflowNotification;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * A dedicated log of this module's own notifications — distinct from the
 * app-wide bell-icon dropdown (which mixes every module's notifications)
 * and from Settings (company-wide channel/threshold configuration).
 */
class WorkflowNotifications extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getModuleSlug(): ?string
    {
        return 'workflow.view';
    }

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'notifications';

    protected static ?string $title = 'Notifications';

    protected static string $view = 'filament.clusters.workflow-approvals.pages.workflow-notifications';

    public function table(Table $table): Table
    {
        return $table
            ->query(Auth::user()->notifications()->where('type', WorkflowNotification::class)->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('data.title')->label('Notification')->weight('semibold'),
                Tables\Columns\TextColumn::make('data.body')->label('Details')->limit(60)->wrap(),
                Tables\Columns\TextColumn::make('data.color')->label('Type')->badge()
                    ->color(fn (?string $state) => $state ?: 'gray')
                    ->formatStateUsing(fn ($record) => $record->data['type'] ?? '—'),
                Tables\Columns\IconColumn::make('read_at')->label('Read')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('markRead')
                    ->label('Mark Read')
                    ->icon('heroicon-o-check')
                    ->visible(fn ($record) => $record->read_at === null)
                    ->action(fn ($record) => $record->markAsRead()),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
