<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Traits\HasModuleAccess;

class PaymentResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'invoicing';
    }

    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Financials';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('invoice_id')
                    ->label('Invoice')
                    ->options(function () {
                        $companyId = auth()->user()?->company_id;
                        return Invoice::when($companyId, fn ($q) => $q->where('company_id', $companyId))
                            ->whereIn('status', ['draft', 'sent', 'overdue'])
                            ->get()
                            ->mapWithKeys(fn ($i) => [$i->id => $i->invoice_number . ' — ' . ($i->client?->company_name ?? '—') . ' (KES ' . number_format($i->total_amount, 2) . ')']);
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($invoice = Invoice::find($state)) {
                            $set('client_id', $invoice->client_id);
                            $set('invoice_number', $invoice->invoice_number);
                            $set('amount', $invoice->total_amount);
                        }
                    })
                    ->nullable(),

                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('invoice_number')
                    ->required()
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix(session('currency', 'KES'))
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'Pending'       => 'Pending',
                        'Paid'          => 'Paid',
                        'Partially Paid'=> 'Partially Paid',
                    ])
                    ->required(),

                Forms\Components\DatePicker::make('payment_date')
                    ->default(now())
                    ->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),

                // FIXED: Using TextColumn here instead of Select component
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paid' => 'success',
                        'Pending' => 'warning',
                        'Partially Paid' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Paid' => 'Paid',
                        'Pending' => 'Pending',
                        'Partially Paid' => 'Partially Paid',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function (Payment $record) {
                        if ($record->status === 'Paid' && $record->invoice_id) {
                            Invoice::where('id', $record->invoice_id)->update(['status' => 'paid']);
                            Notification::make()->title('Invoice marked as paid')->success()->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
