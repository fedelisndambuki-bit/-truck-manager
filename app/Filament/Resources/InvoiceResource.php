<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Helpers\AppSettings;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

// Form components
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\ToggleButtons;
use Closure;
use App\Models\Client;
use App\Models\Truck;
use App\Models\Employee;

// For Email Action
use Filament\Notifications\Notification;
use App\Mail\InvoiceMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;


use App\Traits\HasModuleAccess;

class InvoiceResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'invoicing';
    }

    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Financials';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Invoice Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('invoice_number')
                                    ->default(fn () => 'INV-' . str_pad(Invoice::count() + 1, 4, '0', STR_PAD_LEFT)) // Auto-generate
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->required()
                                    ->maxLength(255),
                                Select::make('client_id')
                                    ->relationship('client', 'company_name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                ToggleButtons::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'sent' => 'Sent',
                                        'paid' => 'Paid',
                                        'overdue' => 'Overdue',
                                    ])
                                    ->colors([
                                        'draft' => 'warning',
                                        'sent' => 'info',
                                        'paid' => 'success',
                                        'overdue' => 'danger',
                                    ])
                                    ->inline()
                                    ->default('draft')
                                    ->required(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('truck_id')
                                    ->relationship('truck', 'plate_number')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                Select::make('driver_id')
                                    ->relationship('driver', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('route_from')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('route_to')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('invoice_date')
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('due_date')
                                    ->required(),
                            ]),
                    ])->columns(2), // This might be overridden by inner Grid columns

                Section::make('Line Items')
                    ->schema([
                        Repeater::make('line_items')
                            ->schema([
                                TextInput::make('description')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->live()
                                    ->required()
                                    ->default(1),
                                TextInput::make('rate')
                                    ->numeric()
                                    ->live()
                                    ->required()
                                    ->default(0)
                                    ->prefix(session('currency', 'KES')),
                            ])
                            ->columns(3)
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                static::updateTotals($get, $set); // Use static::
                            })
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('subtotal')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->prefix(session('currency', 'KES')),
                                TextInput::make('tax')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->prefix(session('currency', 'KES')),
                                TextInput::make('total_amount')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->prefix(session('currency', 'KES')),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function updateTotals(Forms\Get $get, Forms\Set $set): void
    {
        $lineItems = collect($get('line_items'));

        $subtotal = $lineItems->sum(fn (array $item) => $item['quantity'] * $item['rate']);
        $tax = $subtotal * AppSettings::taxRate();
        $totalAmount = $subtotal + $tax;

        $set('subtotal', number_format($subtotal, 2, '.', ''));
        $set('tax', number_format($tax, 2, '.', ''));
        $set('total_amount', number_format($totalAmount, 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->copyable()
                    ->copyMessage('Invoice number copied'),
                TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('client.company_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('truck.plate_number')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('route_from')
                    ->label('Route')
                    ->formatStateUsing(fn ($record) => "{$record->route_from} → {$record->route_to}"),
                TextColumn::make('total_amount')
                    ->money(session('currency', 'KES'))
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->due_date?->isPast() && $record->status !== 'paid' ? 'danger' : null)
                    ->description(fn ($record) => $record->due_date?->isPast() && $record->status !== 'paid' ? 'Overdue' : null),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'   => 'gray',
                        'sent'    => 'info',
                        'paid'    => 'success',
                        'overdue' => 'danger',
                        default   => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                    ])
                    ->label('Filter by Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalIcon('heroicon-o-eye')
                    ->modalIconColor('gray'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (Invoice $record): string => route('invoices.pdf', ['invoice' => $record->id]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('email')
                    ->label('Email Invoice')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->action(function (Invoice $record) {
                        static::sendInvoiceEmail($record);
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('pay')
                    ->label('Pay with Flutterwave')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'paid')
                    ->action(function ($record, \App\Services\PaymentService $service) {
                        try {
                            $checkoutUrl = $service->initializePayment($record->id, $record->company_id);
                            return redirect()->away($checkoutUrl);
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error initializing payment')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('whatsapp')

                    ->label('Send WhatsApp')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->color('success')
                    ->url(fn (Invoice $record): string => static::generateWhatsAppLink($record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->striped();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['client', 'truck']);
        $user  = auth()->user();
        if ($user) {
            $query->where('company_id', $user->company_id);
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    // New method to handle email sending logic
    public static function sendInvoiceEmail(Invoice $invoice): void
    {
        // Generate PDF
        $company = (object)[
            'name' => 'Truck Manager Inc.',
            'address' => '123 Trucking Road, Transport City',
            'phone' => '+1 (555) 123-4567',
            'email' => 'info@truckmanager.com',
            'company_logo' => 'company_logos/truck_manager_logo.png'
        ];

        $companyLogoPath = public_path('storage/' . $company->company_logo);
        if (!Storage::disk('public')->exists($company->company_logo)) {
            $companyLogoPath = null;
        }

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'company', 'companyLogoPath'));
        $pdfContent = $pdf->output();

        // Store PDF temporarily
        $fileName = 'invoice-' . $invoice->invoice_number . '.pdf';
        Storage::disk('local')->put('temp/' . $fileName, $pdfContent);
        $pdfPath = Storage::disk('local')->path('temp/' . $fileName);

        try {
            Mail::to($invoice->client->email)->send(new InvoiceMail($invoice, $pdfPath));
            Notification::make()
                ->title('Invoice Sent')
                ->success()
                ->body('Invoice ' . $invoice->invoice_number . ' emailed successfully to ' . $invoice->client->email . '.')
                ->send();
            $invoice->status = 'sent';
            $invoice->save();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Sending Invoice')
                ->danger()
                ->body('Could not email invoice ' . $invoice->invoice_number . ': ' . $e->getMessage())
                ->send();
        } finally {
            // Delete temporary PDF
            if (Storage::disk('local')->exists('temp/' . $fileName)) {
                Storage::disk('local')->delete('temp/' . $fileName);
            }
        }
    }

    public static function generateWhatsAppLink(Invoice $invoice): string
    {
        $phone = $invoice->client->phone ?? '';
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }

        $message = "Hello " . ($invoice->client->company_name ?? 'Client') . ",\n\n";
        $message .= "Your invoice *" . $invoice->invoice_number . "* for amount *KES " . number_format($invoice->total_amount, 2) . "* is ready.\n\n";
        $message .= "View details: " . url('/invoices/' . $invoice->invoice_number) . "\n\n";
        $message .= "Thank you for choosing Truck Manager.";

        return "https://wa.me/" . $phone . "?text=" . urlencode($message);
    }
}
