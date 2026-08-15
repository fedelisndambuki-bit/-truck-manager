<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\LandingAnalyticResource\Pages;
use App\Filament\SuperAdmin\Resources\LandingAnalyticResource\RelationManagers;
use App\Models\LandingAnalytic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LandingAnalyticResource extends Resource
{
    protected static ?string $model = LandingAnalytic::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Landing Page Manager';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\TextInput::make('visitors')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('trial_signups')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('paid_conversions')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('traffic_source')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('visitors')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('trial_signups')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_conversions')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('traffic_source')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLandingAnalytics::route('/'),
            'create' => Pages\CreateLandingAnalytic::route('/create'),
            'edit' => Pages\EditLandingAnalytic::route('/{record}/edit'),
        ];
    }
}
