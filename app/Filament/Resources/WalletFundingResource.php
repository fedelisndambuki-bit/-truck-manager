<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletFundingResource\Pages;
use App\Models\WalletFunding;
use App\Models\Wallet;
use App\Services\WalletFundingService;
use App\Services\FinancialService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class WalletFundingResource extends Resource
{
    protected static ?string $model = WalletFunding::class;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $navigationGroup = 'Financials';
    protected static ?string $label = 'Wallet Funding';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('amount')->money('KES', divideBy: 100),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'successful' => 'success',
                        'failed', 'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_method'),
                Tables\Columns\TextColumn::make('flutterwave_tx_ref')->searchable()->limit(10),
                Tables\Columns\TextColumn::make('fundedBy.name')->label('Funded By'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'successful' => 'Successful',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('fund_wallet')
                    ->label('Fund Wallet')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\Select::make('wallet_id')
                            ->label('Select Wallet')
                            ->options(fn () => Wallet::where('company_id', auth()->user()->company_id)
                                ->pluck('type', 'id')
                                ->map(fn($v) => ucfirst(str_replace('_', ' ', $v))))
                            ->required()
                            ->default(fn() => Wallet::where('company_id', auth()->user()->company_id)->where('type', 'revenue')->first()?->id),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (KES)')
                            ->numeric()
                            ->required()
                            ->minValue(100),
                    ])
                    ->action(function (array $data, WalletFundingService $service) {
                        try {
                            $link = $service->initialize(
                                auth()->user()->company_id,
                                $data['wallet_id'],
                                (int)($data['amount'] * 100), // Minor units
                                'KES',
                                auth()->id()
                            );

                            return redirect()->away($link);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error initializing funding')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_link')
                    ->label('Payment Link')
                    ->icon('heroicon-o-link')
                    ->url(fn ($record) => $record->payment_link)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->status === 'pending'),
                Tables\Actions\Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['failed', 'cancelled']))
                    ->action(function ($record, WalletFundingService $service) {
                        try {
                            $link = $service->initialize(
                                $record->company_id,
                                $record->wallet_id,
                                $record->amount,
                                $record->currency,
                                auth()->id()
                            );
                            
                            Notification::make()
                                ->title('New funding session initialized')
                                ->success()
                                ->send();

                            return redirect()->away($link);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error retrying funding')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth()->user();
        if ($user) {
            $query->where('company_id', $user->company_id);
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWalletFundings::route('/'),
        ];
    }
}

