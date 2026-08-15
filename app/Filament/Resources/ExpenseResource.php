<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Traits\HasModuleAccess;

class ExpenseResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'expense-tracking';
    }
    protected static ?string $model = Expense::class;

    protected static ?string $navigationGroup = 'Financials';

    // Distinct icon: chart bar for expenses/analytics
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Expense Details')
                ->description('Record costs associated with fleet operations.')
                ->schema([
                    Forms\Components\Select::make('category')
                        ->options([
                            'Salary' => 'Salary',
                            'Allowance' => 'Allowance',
                            'Fuel' => 'Fuel',
                            'Repair' => 'Repair',
                            'Toll' => 'Toll',
                            'Other' => 'Other',
                        ])
                        ->native(false)
                        ->live() // Crucial for conditional fields
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'Draft' => 'Draft',
                            'Approved' => 'Approved',
                            'Paid' => 'Paid',
                        ])
                        ->default('Draft')
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->prefix(session('currency', 'KES'))
                        ->required()
                        ->minValue(1),

                    Forms\Components\Select::make('payslip_earning_type')
                        ->label('Payslip Earning Type')
                        ->options([
                            'standard_pay' => 'Standard Pay',
                            'overtime_pay' => 'Overtime Pay',
                            'holiday_pay' => 'Holiday Pay',
                            'basic_pay' => 'Basic Pay',
                            'commission_bonus' => 'Commission and Bonus',
                            'sick_pay' => 'Sick Pay',
                            'expenses' => 'Expenses',
                        ])
                        ->native(false)
                        ->helperText('Used by payslip PDF to place this amount on the correct earning row.')
                        ->nullable(),

                    Forms\Components\TextInput::make('hours')
                        ->label('Hours')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.25)
                        ->nullable(),

                    Forms\Components\TextInput::make('rate')
                        ->label('Rate')
                        ->numeric()
                        ->prefix(session('currency', 'KES'))
                        ->minValue(0)
                        ->nullable(),

                    // Conditional Taxes Section
                    Forms\Components\Section::make('Tax Details')
                        ->description('Specify tax type and amount if this is a salary-related expense.')
                        ->schema([
                            Forms\Components\Select::make('tax_type')
                                ->options([
                                    'House Levy' => 'House Levy',
                                    'VAT' => 'VAT',
                                    'SHA' => 'SHA',
                                    'NSSF' => 'NSSF',
                                    'PAYE Tax' => 'PAYE Tax',
                                    'National Insurance' => 'National Insurance',
                                    'Pension' => 'Pension',
                                    'Withholding Tax' => 'Withholding Tax',
                                ])
                                ->multiple()
                                ->native(false)
                                ->live() // Crucial for conditional fields
                                ->nullable()
                                ->helperText('Select one or more taxes. Matching value fields will appear below.')
                                ->afterStateHydrated(function (Forms\Components\Select $component, $state): void {
                                    if (is_array($state)) {
                                        return;
                                    }

                                    if (is_string($state) && $state !== '') {
                                        $decoded = json_decode($state, true);
                                        if (is_array($decoded)) {
                                            $component->state($decoded);

                                            return;
                                        }

                                        $component->state([$state]);
                                    }
                                })
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state): void {
                                    $selected = is_array($state) ? array_values(array_filter($state)) : [];
                                    $existing = $get('tax_breakdown');
                                    $existingRows = is_array($existing) ? $existing : [];
                                    $mapped = [];
                                    foreach ($selected as $tax) {
                                        $found = collect($existingRows)->first(fn ($r) => (($r['tax_name'] ?? null) === $tax));
                                        $mapped[] = [
                                            'tax_name' => $tax,
                                            'amount' => (float) ($found['amount'] ?? 0),
                                            'tax_percentage' => (float) ($found['tax_percentage'] ?? ($found['vat_percentage'] ?? 0)),
                                        ];
                                    }
                                    $set('tax_breakdown', $mapped);
                                }),
                            Forms\Components\Repeater::make('tax_breakdown')
                                ->label('Tax Values')
                                ->schema([
                                    Forms\Components\TextInput::make('tax_name')
                                        ->label('Tax')
                                        ->disabled()
                                        ->columnSpanFull()
                                        ->dehydrated(),
                                    Forms\Components\TextInput::make('amount')
                                        ->label('Amount')
                                        ->numeric()
                                        ->prefix(session('currency', 'KES'))
                                        ->minValue(0)
                                        ->columnSpanFull()
                                        ->extraInputAttributes([
                                            'style' => 'min-height: 58px; font-size: 18px; padding: 14px 16px;',
                                        ])
                                        ->required(fn (Forms\Get $get): bool => !in_array(strtolower((string) $get('tax_name')), ['vat', 'withholding tax']))
                                        ->hidden(fn (Forms\Get $get): bool => in_array(strtolower((string) $get('tax_name')), ['vat', 'withholding tax'])),
                                    Forms\Components\TextInput::make('tax_percentage')
                                        ->label('Percentage %')
                                        ->numeric()
                                        ->suffix('%')
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->columnSpanFull()
                                        ->extraInputAttributes([
                                            'style' => 'min-height: 58px; font-size: 18px; padding: 14px 16px;',
                                        ])
                                        ->required(fn (Forms\Get $get): bool => in_array(strtolower((string) $get('tax_name')), ['vat', 'withholding tax']))
                                        ->hidden(fn (Forms\Get $get): bool => !in_array(strtolower((string) $get('tax_name')), ['vat', 'withholding tax'])),
                                ])
                                ->columns(1)
                                ->default([])
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->visible(fn (Forms\Get $get): bool => filled($get('tax_type'))),
                            Forms\Components\Select::make('payslip_deduction_type')
                                ->label('Payslip Deduction Type')
                                ->options([
                                    'paye_tax' => 'PAYE Tax',
                                    'national_insurance' => 'National Insurance',
                                    'pension' => 'Pension',
                                    'withholding_tax' => 'Withholding Tax',
                                ])
                                ->native(false)
                                ->nullable()
                                ->helperText('Overrides deduction row in payslip PDF.'),
                            Forms\Components\TextInput::make('tax_amount')
                                ->numeric()
                                ->prefix(session('currency', 'KES'))
                                ->minValue(0)
                                ->helperText('Auto-calculated from the tax values below when you save.')
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->visible(fn (Forms\Get $get): bool => $get('category') === 'Salary') // Only visible if category is Salary
                        ->columns(3),

                    Forms\Components\DatePicker::make('date')
                        ->default(now())
                        ->required()
                        ->maxDate(now()),

                    Forms\Components\Select::make('payment_method')
                        ->options([
                            'Cash' => 'Cash',
                            'M-Pesa' => 'M-Pesa',
                            'Bank Transfer' => 'Bank Transfer',
                        ])
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('trip_id')
                        ->relationship('trip', 'route_name')
                        ->label('Linked Trip')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Optionally link this expense to a trip.'),

                    Forms\Components\Select::make('truck_id')
                        ->relationship('truck', 'plate_number')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('employee_id')
                        ->relationship('driver', 'full_name')
                        ->label('Driver')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\FileUpload::make('receipt_upload')
                        ->directory('expenses')
                        ->image()
                        ->openable()
                        ->downloadable()
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Fuel' => 'danger',
                        'Repair' => 'warning',
                        'Allowance' => 'success',
                        'Toll' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'Approved' => 'info',
                        'Paid' => 'success',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->money()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')),

                Tables\Columns\TextColumn::make('truck.plate_number')
                    ->label('Truck')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('driver.full_name')
                    ->label('Driver')
                    ->placeholder('No Driver Assigned'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category'),
                Tables\Filters\SelectFilter::make('truck')
                    ->relationship('truck', 'plate_number'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn (Expense $record) => $record->status === 'Draft')
                    ->action(function (Expense $record) {
                        $record->update([
                            'status'      => 'Approved',
                            'approver_id' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Expense approved')
                            ->body('Approved by ' . auth()->user()->name . ' on ' . now()->format('d M Y'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('pay')
                    ->label('Pay')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Expense $record) => $record->status === 'Approved')
                    ->requiresConfirmation()
                    ->action(fn (Expense $record) => $record->update(['status' => 'Paid'])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->striped();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth()->user();
        if ($user) {
            $query->where('company_id', $user->company_id);
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
