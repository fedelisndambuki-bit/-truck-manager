<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PutAwayTaskResource\Pages;
use App\Models\BinLocation;
use App\Models\PutAwayTask;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PutAwayTaskResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'materials.view';
    }

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $model = PutAwayTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'Put-Away';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 12;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task_number')->label('Task #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('goodsReceivedNote.grn_number')->label('GRN #')->placeholder('—'),
                Tables\Columns\TextColumn::make('material.name')->label('Material'),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Warehouse'),
                Tables\Columns\TextColumn::make('quantity')->numeric(2),
                Tables\Columns\TextColumn::make('recommendedBin.code')->label('Recommended Bin')->placeholder('—'),
                Tables\Columns\TextColumn::make('actualBin.code')->label('Actual Bin')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Completed' => 'success', 'Cancelled' => 'danger', default => 'warning',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['Pending' => 'Pending', 'Completed' => 'Completed', 'Cancelled' => 'Cancelled']),
                Tables\Filters\SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('complete')
                    ->label('Complete Put-Away')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PutAwayTask $record) => $record->status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('materials.putaway')))
                    ->form(fn (PutAwayTask $record) => [
                        Forms\Components\Select::make('bin_location_id')
                            ->label('Bin')
                            ->options(fn () => BinLocation::where('warehouse_id', $record->warehouse_id)
                                ->where('status', 'Active')
                                ->pluck('code', 'id'))
                            ->default($record->recommended_bin_id)
                            ->searchable()->required(),
                    ])
                    ->action(function (PutAwayTask $record, array $data) {
                        $bin = BinLocation::find($data['bin_location_id']);
                        $record->complete($bin);
                        Notification::make()->title('Put-away completed')->success()->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (PutAwayTask $record) => $record->status === 'Pending')
                    ->action(fn (PutAwayTask $record) => $record->update(['status' => 'Cancelled'])),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPutAwayTasks::route('/'),
        ];
    }
}
