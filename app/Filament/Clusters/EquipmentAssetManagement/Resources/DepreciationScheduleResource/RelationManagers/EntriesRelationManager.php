<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\DepreciationScheduleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    protected static ?string $title = 'Posted Periods';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('period_start')->required(),
            Forms\Components\DatePicker::make('period_end')->required(),
            Forms\Components\TextInput::make('amount')->numeric()->prefix(session('currency', 'KES'))->required(),
            Forms\Components\TextInput::make('book_value_after')->numeric()->prefix(session('currency', 'KES'))->required(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('period_end')
            ->columns([
                Tables\Columns\TextColumn::make('period_start')->date(),
                Tables\Columns\TextColumn::make('period_end')->date()->sortable(),
                Tables\Columns\TextColumn::make('amount')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('book_value_after')->label('Book Value After')->money(session('currency', 'KES')),
                Tables\Columns\IconColumn::make('ledger_posted')->label('Posted to Finance')->boolean(),
            ])
            ->defaultSort('period_end', 'desc')
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
