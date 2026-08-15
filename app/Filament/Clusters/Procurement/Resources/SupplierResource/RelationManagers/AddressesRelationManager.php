<?php

namespace App\Filament\Clusters\Procurement\Resources\SupplierResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Addresses';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('label')->placeholder('e.g. Head Office, Warehouse'),
            Forms\Components\Textarea::make('address_line')->required()->columnSpanFull(),
            Forms\Components\TextInput::make('city'),
            Forms\Components\TextInput::make('country'),
            Forms\Components\Toggle::make('is_primary')->label('Primary Address'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address_line')
            ->columns([
                Tables\Columns\TextColumn::make('label')->placeholder('—'),
                Tables\Columns\TextColumn::make('address_line')->limit(50),
                Tables\Columns\TextColumn::make('city')->placeholder('—'),
                Tables\Columns\TextColumn::make('country')->placeholder('—'),
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
