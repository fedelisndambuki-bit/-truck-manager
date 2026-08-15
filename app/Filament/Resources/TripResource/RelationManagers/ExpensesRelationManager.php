<?php

namespace App\Filament\Resources\TripResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Trip Expenses';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category')
                ->options([
                    'Fuel'      => 'Fuel',
                    'Repair'    => 'Repair',
                    'Toll'      => 'Toll',
                    'Allowance' => 'Allowance',
                    'Other'     => 'Other',
                ])
                ->required()
                ->native(false),
            Forms\Components\TextInput::make('amount')
                ->numeric()
                ->prefix(session('currency', 'KES'))
                ->required()
                ->minValue(1),
            Forms\Components\Select::make('payment_method')
                ->options([
                    'Cash'          => 'Cash',
                    'M-Pesa'        => 'M-Pesa',
                    'Bank Transfer' => 'Bank Transfer',
                ])
                ->required()
                ->native(false),
            Forms\Components\DatePicker::make('date')
                ->default(now())
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'Draft'    => 'Draft',
                    'Approved' => 'Approved',
                    'Paid'     => 'Paid',
                ])
                ->default('Draft')
                ->required()
                ->native(false),
            Forms\Components\Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Fuel'   => 'danger',
                        'Repair' => 'warning',
                        default  => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')->money()->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Draft'    => 'gray',
                        'Approved' => 'info',
                        'Paid'     => 'success',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_method')->badge()->color('gray'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }
}
