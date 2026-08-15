<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\TyreResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RepairsRelationManager extends RelationManager
{
    protected static string $relationship = 'repairs';

    protected static ?string $title = 'Repairs';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('repair_date')->default(now())->required(),
            Forms\Components\Select::make('repair_type')->options([
                'puncture' => 'Puncture', 'vulcanization' => 'Vulcanization', 'retread' => 'Retread',
            ])->required(),
            Forms\Components\TextInput::make('cost')->numeric()->prefix(session('currency', 'KES')),
            Forms\Components\TextInput::make('vendor'),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('repair_type')
            ->columns([
                Tables\Columns\TextColumn::make('repair_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('repair_type')->badge(),
                Tables\Columns\TextColumn::make('cost')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('vendor')->placeholder('—'),
            ])
            ->defaultSort('repair_date', 'desc')
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
