<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\TyreResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InspectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'inspections';

    protected static ?string $title = 'Inspections';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('inspection_date')->default(now())->required(),
            Forms\Components\TextInput::make('tread_depth')->numeric()->suffix('mm'),
            Forms\Components\TextInput::make('pressure')->numeric()->suffix('psi'),
            Forms\Components\TextInput::make('temperature')->numeric()->suffix('°C'),
            Forms\Components\KeyValue::make('checklist')->columnSpanFull(),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('inspection_date')
            ->columns([
                Tables\Columns\TextColumn::make('inspection_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('tread_depth')->suffix(' mm')->placeholder('—'),
                Tables\Columns\TextColumn::make('pressure')->suffix(' psi')->placeholder('—'),
                Tables\Columns\TextColumn::make('temperature')->suffix(' °C')->placeholder('—'),
            ])
            ->defaultSort('inspection_date', 'desc')
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
