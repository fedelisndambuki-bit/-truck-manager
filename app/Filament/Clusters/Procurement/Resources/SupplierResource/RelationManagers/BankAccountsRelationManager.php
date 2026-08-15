<?php

namespace App\Filament\Clusters\Procurement\Resources\SupplierResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BankAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'bankAccounts';

    protected static ?string $title = 'Bank Accounts';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('bank_name')->required(),
            Forms\Components\TextInput::make('account_name'),
            Forms\Components\TextInput::make('account_number')->required(),
            Forms\Components\TextInput::make('branch'),
            Forms\Components\TextInput::make('swift_code'),
            Forms\Components\TextInput::make('currency')->default('KES'),
            Forms\Components\Toggle::make('is_primary')->label('Primary Account'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('account_number')
            ->columns([
                Tables\Columns\TextColumn::make('bank_name'),
                Tables\Columns\TextColumn::make('account_number'),
                Tables\Columns\TextColumn::make('branch')->placeholder('—'),
                Tables\Columns\TextColumn::make('currency'),
                Tables\Columns\IconColumn::make('is_primary')->label('Primary')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
