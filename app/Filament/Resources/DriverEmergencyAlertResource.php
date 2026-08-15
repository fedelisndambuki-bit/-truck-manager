<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverEmergencyAlertResource\Pages;
use App\Models\DriverEmergencyAlert;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use App\Traits\HasModuleAccess;

class DriverEmergencyAlertResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'driver-management';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }
    protected static ?string $model = DriverEmergencyAlert::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Driver Submissions';

    protected static ?string $navigationLabel = 'Emergency Alerts';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $count = DriverEmergencyAlert::query()
            ->when(! $user->hasRole('Super Admin'), fn (Builder $q) => $q->whereHas('driver', fn (Builder $q) => $q->where('company_id', $user->company_id)))
            ->where('status', 'open')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('triggered_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('driver.user.name')->label('Driver')->searchable(),
                Tables\Columns\TextColumn::make('trip.id')->label('Trip')->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->color('danger'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('message')->limit(50)->toggleable(),
                Tables\Columns\TextColumn::make('latitude')->toggleable(),
                Tables\Columns\TextColumn::make('longitude')->toggleable(),
                Tables\Columns\TextColumn::make('triggered_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DriverEmergencyAlert $record): bool => $record->status !== 'resolved')
                    ->action(fn (DriverEmergencyAlert $record) => $record->update(['status' => 'resolved'])),
                Tables\Actions\Action::make('setInProgress')
                    ->label('In Progress')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn (DriverEmergencyAlert $record): bool => $record->status === 'open')
                    ->action(fn (DriverEmergencyAlert $record) => $record->update(['status' => 'in_progress'])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('status')->required(),
            Forms\Components\Textarea::make('message'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverEmergencyAlerts::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user) {
            $query->whereHas('driver', fn (Builder $q) => $q->where('company_id', $user->company_id));
        }

        return $query;
    }
}
