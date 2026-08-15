<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverFuelReceiptResource\Pages;
use App\Models\DriverFuelReceipt;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use App\Traits\HasModuleAccess;

class DriverFuelReceiptResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'fuel-logs';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }
    protected static ?string $model = DriverFuelReceipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Driver Submissions';

    protected static ?string $navigationLabel = 'Fuel Receipt Reviews';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('receipt_path')
                    ->label('Receipt')
                    ->disk('public')
                    ->height(48)
                    ->square(),
                Tables\Columns\TextColumn::make('driver.user.name')
                    ->label('Driver')
                    ->searchable(),
                Tables\Columns\TextColumn::make('trip.id')
                    ->label('Trip')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('litres')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('failure_reason')
                    ->label('Reason')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('captured_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (DriverFuelReceipt $record): void {
                        $record->update([
                            'status' => 'approved',
                            'failure_reason' => null,
                        ]);
                    }),
                Tables\Actions\Action::make('fail')
                    ->label('Fail')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('failure_reason')
                            ->label('Failure Reason')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (DriverFuelReceipt $record, array $data): void {
                        $record->update([
                            'status' => 'failed',
                            'failure_reason' => $data['failure_reason'],
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverFuelReceipts::route('/'),
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
