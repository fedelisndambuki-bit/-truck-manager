<?php

namespace App\Filament\Resources\TenderInvitationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvitedSubcontractorsRelationManager extends RelationManager
{
    protected static string $relationship = 'invitedSubcontractors';

    protected static ?string $title = 'Invited Subcontractors';

    protected static ?string $recordTitleAttribute = 'company_name';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DateTimePicker::make('invited_at')->default(now()),
            Forms\Components\Toggle::make('responded'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('company_name')
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Subcontractor')->searchable(),
                Tables\Columns\TextColumn::make('pivot.invited_at')->label('Invited At')->dateTime()->placeholder('—'),
                Tables\Columns\IconColumn::make('pivot.responded')->label('Responded')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\DateTimePicker::make('invited_at')->default(now()),
                        Forms\Components\Toggle::make('responded'),
                    ]),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
