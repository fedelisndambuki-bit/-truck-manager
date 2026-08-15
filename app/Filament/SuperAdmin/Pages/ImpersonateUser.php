<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ImpersonateUser extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Impersonate User';

    protected static ?string $navigationGroup = 'Security';

    protected static ?int $navigationSort = 69;

    protected static string $view = 'filament.super-admin.pages.impersonate-user';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->with(['roles', 'company'])
                    ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => "Impersonate {$record->name}?")
                    ->modalDescription('You will be logged in as this user and redirected to their admin panel. A log entry will be created.')
                    ->modalSubmitActionLabel('Yes, Impersonate')
                    ->url(fn (User $record) => route('superadmin.impersonation.start.get', $record)),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }
}
