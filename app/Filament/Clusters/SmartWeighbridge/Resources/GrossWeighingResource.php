<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources;

use App\Filament\Clusters\SmartWeighbridge;
use App\Filament\Clusters\SmartWeighbridge\Resources\GrossWeighingResource\Pages;
use App\Models\DispatchOrder;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * 3rd Resource over DispatchOrder — scoped to trucks that have completed
 * loading and are waiting for (or need re-weighing towards) their gross
 * weight, wired to recordGross() + runDecisionEngine().
 */
class GrossWeighingResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = SmartWeighbridge::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'weighbridge.view';
    }

    protected static ?string $model = DispatchOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Gross Weighing';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'gross-weighing';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('status', 'Awaiting Gross Weight'))
            ->defaultSort('loading_completed_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('dispatch_number')->label('Dispatch #')->searchable(),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->searchable(),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer'),
                Tables\Columns\TextColumn::make('tare_weight')->label('Tare')->suffix(' t'),
                Tables\Columns\TextColumn::make('required_gross_weight')->label('Required Gross')->suffix(' t')->placeholder('—'),
                Tables\Columns\TextColumn::make('reweigh_count')->label('Reweighs'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => VehicleCheckInResource::getStatusColor($state)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->infolist(fn (Infolist $infolist) => VehicleCheckInResource::infolist($infolist)),
                Tables\Actions\Action::make('recordGross')
                    ->label('Record Gross Weight')
                    ->icon('heroicon-o-scale')
                    ->color('primary')
                    ->visible(fn (DispatchOrder $record) => $record->status === 'Awaiting Gross Weight')
                    ->form([
                        Forms\Components\TextInput::make('gross_weight')->label('Gross Weight (t)')->numeric()->required(),
                        Forms\Components\Select::make('gross_operator_id')->label('Operator')
                            ->relationship('grossOperator', 'full_name')->searchable()->preload(),
                        Forms\Components\TextInput::make('weather'),
                        Forms\Components\Textarea::make('comments'),
                    ])
                    ->action(function (DispatchOrder $record, array $data) {
                        $record->recordGross($data);
                        $record->runDecisionEngine();
                        $record->refresh();

                        Notification::make()
                            ->title('Gross weight recorded — ' . $record->weight_status . ' (status: ' . $record->status . ')')
                            ->success()
                            ->send();
                    }),
            ])
            ->striped();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['customer']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGrossWeighing::route('/'),
        ];
    }
}
