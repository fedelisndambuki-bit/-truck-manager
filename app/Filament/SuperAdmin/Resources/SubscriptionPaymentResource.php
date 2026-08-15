<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\SubscriptionPaymentResource\Pages;
use App\Models\SubscriptionPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionPaymentResource extends Resource
{
    protected static ?string $model = SubscriptionPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Payments';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?: ('Company #' . $record->id))
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('subscription_id')
                ->relationship('subscription', 'plan_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->plan_name ?: ('Subscription #' . $record->id))
                ->searchable()
                ->preload()
                ->helperText('Marking this "Paid" activates and extends this subscription automatically.'),
            Forms\Components\TextInput::make('amount')->numeric()->required()->prefix(session('currency', 'KES')),
            Forms\Components\TextInput::make('method'),
            Forms\Components\TextInput::make('transaction_reference'),
            Forms\Components\Select::make('status')->options([
                'pending' => 'Pending',
                'paid' => 'Paid',
                'failed' => 'Failed',
                'refunded' => 'Refunded',
            ])->required(),
            Forms\Components\DateTimePicker::make('paid_at'),
            Forms\Components\Section::make('Gateway Reconciliation')
                ->description('Read from the payment gateway — for matching this record against Paystack, not for manual entry.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('paystack_reference')->label('Paystack Reference')->disabled(),
                    Forms\Components\TextInput::make('paystack_transaction_id')->label('Paystack Transaction ID')->disabled(),
                ])->columns(2),
            Forms\Components\Section::make('Billing Details')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('billing_name')->label('Billing Name'),
                    Forms\Components\TextInput::make('billing_email')->label('Billing Email')->email(),
                    Forms\Components\TextInput::make('billing_phone')->label('Billing Phone'),
                    Forms\Components\TextInput::make('billing_country')->label('Country'),
                ])->columns(2),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('company.name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('amount')->money()->sortable(),
            Tables\Columns\TextColumn::make('method')->toggleable(),
            Tables\Columns\TextColumn::make('transaction_reference')->toggleable(),
            Tables\Columns\TextColumn::make('paystack_reference')->label('Paystack Ref')->toggleable(isToggledHiddenByDefault: true)->searchable(),
            Tables\Columns\TextColumn::make('billing_email')->label('Billing Email')->toggleable(isToggledHiddenByDefault: true)->searchable(),
            Tables\Columns\BadgeColumn::make('status')->colors([
                'warning' => 'pending',
                'success' => 'paid',
                'danger' => 'failed',
                'gray' => 'refunded',
            ]),
            Tables\Columns\TextColumn::make('paid_at')->dateTime()->sortable(),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPayments::route('/'),
            'create' => Pages\CreateSubscriptionPayment::route('/create'),
            'edit' => Pages\EditSubscriptionPayment::route('/{record}/edit'),
        ];
    }
}
