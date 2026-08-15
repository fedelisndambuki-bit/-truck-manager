<?php

namespace App\Filament\Resources\TenderInvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ClarificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'clarifications';

    protected static ?string $title = 'Clarifications';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('subcontractor_id')
                ->label('Asked By Subcontractor')
                ->relationship('subcontractor', 'company_name')
                ->searchable()->preload(),
            Forms\Components\DatePicker::make('date')->default(now()),
            Forms\Components\Textarea::make('question')->required()->columnSpanFull(),
            Forms\Components\Textarea::make('answer')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->columns([
                Tables\Columns\TextColumn::make('subcontractor.company_name')->label('Subcontractor')->placeholder('—'),
                Tables\Columns\TextColumn::make('question')->limit(50),
                Tables\Columns\TextColumn::make('answer')->limit(50)->placeholder('Pending'),
                Tables\Columns\TextColumn::make('date')->date(),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }
}
