<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources;

use App\Filament\Clusters\SmartWeighbridge;
use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleCheckInResource;
use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleQueueResource\Pages;
use App\Models\DispatchOrder;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-mostly queue view over the same DispatchOrder table — grouped by
 * status/queue-stage with wait time, plus the "Record Tare" action that
 * triggers recordTare() (which auto-generates the Loading Instruction).
 */
class VehicleQueueResource extends Resource
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

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Vehicle Queue';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'vehicle-queue';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereNotIn('status', ['Approved', 'Exited', 'Cancelled']))
            ->defaultSort('gate_in_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('dispatch_number')->label('Dispatch #')->searchable(),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->searchable(),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => VehicleCheckInResource::getStatusColor($state)),
                Tables\Columns\TextColumn::make('priority')->badge(),
                Tables\Columns\TextColumn::make('waiting_minutes')
                    ->label('Waiting Time')
                    ->state(fn (DispatchOrder $record) => $record->waitingMinutes() !== null ? $record->waitingMinutes() . ' min' : '—'),
                Tables\Columns\TextColumn::make('weighbridge.name')->label('Weighbridge')->placeholder('—'),
            ])
            ->groups([
                Tables\Grouping\Group::make('status')->label('Queue Stage'),
            ])
            ->defaultGroup('status')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(DispatchOrder::STATUSES, DispatchOrder::STATUSES)),
                Tables\Filters\SelectFilter::make('priority')->options(array_combine(DispatchOrder::PRIORITIES, DispatchOrder::PRIORITIES)),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (DispatchOrder $record) => VehicleCheckInResource::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('recordTare')
                    ->label('Record Tare')
                    ->icon('heroicon-o-scale')
                    ->color('primary')
                    ->visible(fn (DispatchOrder $record) => $record->status === 'Checked In')
                    ->form([
                        Forms\Components\TextInput::make('tare_weight')->label('Tare Weight (t)')->numeric()->required(),
                        Forms\Components\Select::make('tare_operator_id')->label('Operator')
                            ->relationship('tareOperator', 'full_name')->searchable()->preload(),
                    ])
                    ->action(function (DispatchOrder $record, array $data) {
                        $record->recordTare($data);
                        Notification::make()->title('Tare recorded — Loading Instruction generated')->success()->send();
                    }),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleQueue::route('/'),
        ];
    }
}
