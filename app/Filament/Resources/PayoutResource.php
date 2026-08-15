<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayoutResource\Pages;
use App\Models\Driver;
use App\Models\Payout;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financials';
    protected static ?string $label           = 'Payouts';
    protected static ?string $navigationLabel = 'Payouts';
    protected static ?int    $navigationSort  = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->state(function (Payout $record): string {
                        if ($record->recipient_type === 'driver') {
                            $name = $record->driver?->user?->name;
                            return $name ?? ('Driver #' . substr($record->recipient_id, 0, 8));
                        }
                        return ucfirst($record->recipient_type) . ' #' . substr($record->recipient_id, 0, 8);
                    })
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('driver.user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    })
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone / Account')
                    ->state(function (Payout $record): string {
                        if ($record->recipient_type === 'driver') {
                            return $record->driver?->user?->phone_number ?? $record->account_number ?? '—';
                        }
                        return $record->account_number ?? '—';
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('recipient_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn ($state) => $state === 'driver' ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => 'KES ' . number_format($state / 100, 2))
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'completed'  => 'success',
                        'processing' => 'warning',
                        'failed'     => 'danger',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'approved'   => 'Approved',
                        'processing' => 'Processing',
                        'completed'  => 'Completed',
                        'failed'     => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('recipient_type')
                    ->label('Type')
                    ->options([
                        'driver' => 'Driver',
                        'vendor' => 'Vendor',
                    ]),

                // Used by DriverWalletResource "Withdrawals" button deep-link
                Tables\Filters\Filter::make('driver_id')
                    ->form([
                        Forms\Components\Hidden::make('driver_id'),
                    ])
                    ->query(function ($query, array $data) {
                        if (filled($data['driver_id'] ?? null)) {
                            $query->where('recipient_type', 'driver')
                                  ->where('recipient_id', $data['driver_id']);
                        }
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (filled($data['driver_id'] ?? null)) {
                            $name = Driver::find($data['driver_id'])?->user?->name;
                            return 'Driver: ' . ($name ?? $data['driver_id']);
                        }
                        return null;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve & Pay')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Payout')
                    ->modalDescription(fn (Payout $record) => 'Send KES ' . number_format($record->amount / 100, 2) . ' to ' . ($record->driver?->user?->name ?? 'recipient') . '?')
                    ->visible(fn (Payout $record) => $record->status === 'pending')
                    ->action(function (Payout $record, \App\Services\PayoutService $service) {
                        try {
                            $service->approveAndExecute($record->id, auth()->id());
                            \Filament\Notifications\Notification::make()
                                ->title('Payout approved and processing')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error processing payout')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
        ];
    }
}
