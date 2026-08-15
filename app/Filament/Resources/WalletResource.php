<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Financials';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('company_id')->required(),
            Forms\Components\Select::make('type')
                ->options([
                    'revenue' => 'Revenue',
                    'expense' => 'Expense',
                    'driver_pool' => 'Driver Pool',
                ])
                ->required(),
            Forms\Components\TextInput::make('available_balance')->numeric()->required(),
            Forms\Components\TextInput::make('ledger_balance')->numeric()->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_id')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('available_balance')
                    ->money(fn ($record) => $record->currency ?? 'KES', divideBy: 100),
                Tables\Columns\TextColumn::make('ledger_balance')
                    ->money(fn ($record) => $record->currency ?? 'KES', divideBy: 100),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListWallets::route('/'),
        ];
    }
}
