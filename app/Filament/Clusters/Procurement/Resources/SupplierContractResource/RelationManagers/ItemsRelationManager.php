<?php

namespace App\Filament\Clusters\Procurement\Resources\SupplierContractResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Negotiated Prices / Volume Commitments';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('material_id')->label('Material')->relationship('material', 'name')->searchable()->preload()->columnSpan(2),
            Forms\Components\TextInput::make('description')->columnSpan(2),
            Forms\Components\TextInput::make('negotiated_price')->numeric()->required()->prefix(session('currency', 'KES')),
            Forms\Components\TextInput::make('volume_commitment')->numeric(),
            Forms\Components\TextInput::make('unit'),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('material.name')->label('Material')->placeholder('—'),
                Tables\Columns\TextColumn::make('description')->placeholder('—'),
                Tables\Columns\TextColumn::make('negotiated_price')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('volume_commitment')->placeholder('—'),
                Tables\Columns\TextColumn::make('unit')->placeholder('—'),
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
