<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverWalletResource\Pages;
use App\Models\Driver;
use App\Models\DriverWallet;
use App\Models\Expense;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DriverWalletResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }

    protected static ?string $model = DriverWallet::class;

    protected static ?string $navigationIcon  = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Financials';
    protected static ?string $label           = 'Driver Wallets';
    protected static ?string $navigationLabel = 'Driver Wallets';
    protected static ?int    $navigationSort  = 3;

    public static function canCreate(): bool
    {
        return false; // wallets are created automatically, not manually
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('driver.user.name')
                    ->label('Driver')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('driver.user.phone_number')
                    ->label('Phone')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('driver.license_number')
                    ->label('License No.')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Wallet Balance')
                    ->formatStateUsing(fn ($state) => 'KES ' . number_format($state / 100, 2))
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('balance', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('top_up')
                    ->label('Top Up')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (KES)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->prefix('KES'),
                        Forms\Components\Select::make('category')
                            ->label('Payment Type')
                            ->options([
                                'Salary'    => 'Salary',
                                'Allowance' => 'Allowance',
                                'Bonus'     => 'Bonus',
                                'Advance'   => 'Advance',
                            ])
                            ->default('Salary')
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('note')
                            ->label('Note (optional)')
                            ->rows(2),
                    ])
                    ->action(function (DriverWallet $record, array $data): void {
                        $driver = Driver::find($record->driver_id);
                        if (! $driver) {
                            Notification::make()->title('Driver not found')->danger()->send();
                            return;
                        }

                        $user = $driver->user;
                        if (! $user) {
                            Notification::make()->title('Driver has no linked user account')->danger()->send();
                            return;
                        }

                        $employee = $driver->employee;

                        if (! $employee) {
                            Notification::make()
                                ->title('No employee record linked to this driver')
                                ->body('Link this driver to an employee record before topping up their wallet.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Create Expense → ExpenseObserver automatically credits DriverWallet
                        // and records a labeled DriverWalletTransaction (salary/bonus/allowance/advance)
                        Expense::create([
                            'company_id'           => $driver->company_id,
                            'employee_id'          => $employee->id,
                            'category'             => $data['category'],
                            'amount'               => $data['amount'],
                            'date'                 => now(),
                            'payment_method'       => 'Wallet',
                            'status'               => 'Paid',
                            'payslip_earning_type' => match ($data['category']) {
                                'Salary'  => 'basic_pay',
                                'Bonus'   => 'commission_bonus',
                                'Advance' => 'basic_pay',
                                default   => 'expenses',
                            },
                            'description' => $data['note'] ?: $data['category'] . ' payment',
                        ]);

                        Notification::make()
                            ->title('KES ' . number_format($data['amount'], 2) . ' credited to ' . $user->name . '\'s wallet')
                            ->body('Payment type: ' . $data['category'])
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('withdrawals')
                    ->label('Withdrawals')
                    ->icon('heroicon-o-arrow-up-right')
                    ->color('gray')
                    ->url(fn (DriverWallet $record) => PayoutResource::getUrl('index') . '?tableFilters[driver_id][value]=' . $record->driver_id)
                    ->openUrlInNewTab(false),
            ])
            ->bulkActions([])
            ->striped()
            ->emptyStateHeading('No driver wallets yet')
            ->emptyStateDescription('Wallets are created automatically when a driver receives their first payment.');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['driver']);
        $user  = auth()->user();
        if ($user) {
            $query->where('company_id', $user->company_id);
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverWallets::route('/'),
        ];
    }
}
