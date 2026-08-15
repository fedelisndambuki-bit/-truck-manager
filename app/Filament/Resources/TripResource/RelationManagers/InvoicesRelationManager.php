<?php

namespace App\Filament\Resources\TripResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Invoice;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Invoices';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('invoice_number')
                ->default(fn () => 'INV-' . str_pad(Invoice::count() + 1, 4, '0', STR_PAD_LEFT))
                ->disabled()
                ->dehydrated()
                ->required(),
            Forms\Components\Select::make('client_id')
                ->relationship('client', 'company_name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'draft'   => 'Draft',
                    'sent'    => 'Sent',
                    'paid'    => 'Paid',
                    'overdue' => 'Overdue',
                ])
                ->default('draft')
                ->required()
                ->native(false),
            Forms\Components\DatePicker::make('invoice_date')->default(now())->required(),
            Forms\Components\DatePicker::make('due_date')->required(),
            Forms\Components\TextInput::make('total_amount')
                ->numeric()
                ->prefix(session('currency', 'KES'))
                ->required(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),
                Tables\Columns\TextColumn::make('client.company_name'),
                Tables\Columns\TextColumn::make('total_amount')->money(),
                Tables\Columns\TextColumn::make('due_date')->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft'   => 'gray',
                        'sent'    => 'info',
                        'paid'    => 'success',
                        'overdue' => 'danger',
                        default   => 'gray',
                    }),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
