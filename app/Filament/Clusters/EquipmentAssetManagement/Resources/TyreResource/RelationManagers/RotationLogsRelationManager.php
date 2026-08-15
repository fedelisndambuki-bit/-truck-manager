<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\TyreResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RotationLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'rotationLogs';

    protected static ?string $title = 'Rotation History';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('from_position'),
            Forms\Components\TextInput::make('to_position')->required(),
            Forms\Components\DateTimePicker::make('rotated_at')->default(now())->required(),
            Forms\Components\TextInput::make('mileage_at_rotation')->numeric(),
            Forms\Components\Select::make('performed_by_id')->label('Performed By')->relationship('performedBy', 'full_name')->searchable()->preload(),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('to_position')
            ->columns([
                Tables\Columns\TextColumn::make('rotated_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('from_position')->placeholder('—'),
                Tables\Columns\TextColumn::make('to_position'),
                Tables\Columns\TextColumn::make('performedBy.full_name')->label('Performed By')->placeholder('—'),
            ])
            ->defaultSort('rotated_at', 'desc')
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
