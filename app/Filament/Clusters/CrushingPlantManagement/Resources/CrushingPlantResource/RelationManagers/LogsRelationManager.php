<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Shift Logs';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')->required()->default(now()),
            Forms\Components\Select::make('shift')->options(['Day' => 'Day', 'Night' => 'Night'])->default('Day')->required(),
            Forms\Components\TextInput::make('throughput_tonnes')->label('Throughput')->numeric()->suffix('t')->default(0)->required(),
            Forms\Components\TextInput::make('output_tonnes')->label('Output')->numeric()->suffix('t')->default(0)->required(),
            Forms\Components\TextInput::make('fuel_usage_litres')->label('Fuel Usage')->numeric()->suffix('L'),
            Forms\Components\TextInput::make('power_consumption_kwh')->label('Power Consumption')->numeric()->suffix('kWh'),
            Forms\Components\TextInput::make('downtime_minutes')->label('Downtime')->numeric()->suffix('min')->default(0),
            Forms\Components\Select::make('operator_id')->label('Operator')->relationship('operator', 'full_name')->searchable()->preload(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('date')
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('shift')->badge(),
                Tables\Columns\TextColumn::make('throughput_tonnes')->label('Throughput')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('output_tonnes')->label('Output')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('downtime_minutes')->label('Downtime')->numeric()->suffix(' min'),
                Tables\Columns\TextColumn::make('operator.full_name')->label('Operator')->placeholder('—'),
            ])
            ->defaultSort('date', 'desc')
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
