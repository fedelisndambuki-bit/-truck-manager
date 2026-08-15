<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\SubscriptionResource\Pages;
use App\Models\Plan;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Subscriptions';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record): string => (string) ($record->name ?: "Company #{$record->id}"))
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('plan_id')
                ->relationship('plan', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record): string => (string) ($record->name ?: "Plan #{$record->id}"))
                ->searchable()
                ->preload()
                ->live()
                ->helperText('Selecting a plan fills in the fields below from it — you can still adjust them manually afterward.')
                ->afterStateUpdated(function (?string $state, Set $set) {
                    $plan = $state ? Plan::find($state) : null;

                    if (! $plan) {
                        return;
                    }

                    $set('plan_name', $plan->name);
                    $set('price', $plan->price);
                    $set('truck_limit', $plan->truck_limit);
                    $set('driver_limit', $plan->driver_limit);
                    $set('ai_limit', $plan->ai_requests_limit);
                }),
            Forms\Components\TextInput::make('plan_name')->required(),
            Forms\Components\TextInput::make('price')->numeric()->prefix(session('currency', 'KES'))->required(),
            Forms\Components\TextInput::make('truck_limit')->numeric()->default(0)->required(),
            Forms\Components\TextInput::make('driver_limit')->numeric()->default(0)->required(),
            Forms\Components\TextInput::make('ai_limit')->numeric()->default(0)->required(),
            Forms\Components\Select::make('billing_cycle')->options([
                'monthly' => 'Monthly',
                'yearly' => 'Yearly',
            ])->required(),
            Forms\Components\Select::make('status')->options([
                'active' => 'Active',
                'expired' => 'Expired',
                'canceled' => 'Canceled',
                'trial' => 'Trial',
            ])->required(),
            Forms\Components\Toggle::make('auto_renewal')
                ->label('Auto-Renewal')
                ->helperText('Whether this subscription should attempt to renew automatically at expiry.')
                ->default(true),
            Forms\Components\DateTimePicker::make('started_at'),
            Forms\Components\DateTimePicker::make('expires_at'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => ['expired', 'canceled', 'inactive'],
                        'warning' => 'trial',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->label('Cycle')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('limits')
                    ->label('Limits')
                    ->state(function (Subscription $record): string {
                        return "Truck: {$record->truck_limit} | Driver: {$record->driver_limit} | AI: {$record->ai_limit}";
                    })
                    ->description(fn (Subscription $record) => 'Trucks, Drivers, AI Requests'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('auto_renewal')
                    ->label('Auto-Renew')
                    ->boolean()
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
