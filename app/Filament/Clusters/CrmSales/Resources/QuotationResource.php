<?php

namespace App\Filament\Clusters\CrmSales\Resources;

use App\Filament\Clusters\CrmSales;
use App\Filament\Clusters\CrmSales\Resources\QuotationResource\Pages;
use App\Models\Quotation;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class QuotationResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'crm.view';
    }

    protected static ?string $model = Quotation::class;

    protected static ?string $cluster = CrmSales::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationLabel = 'Quotations';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'quotation_number';

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Accepted', 'Approved' => 'success',
            'Rejected', 'Expired' => 'danger',
            'Submitted' => 'warning',
            default => 'gray',
        };
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['client']);
    }

    public static function form(Form $form): Form
    {
        $currency = session('currency', 'KES');

        return $form
            ->schema([
                Forms\Components\Section::make('Quotation Details')->schema([
                    Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->relationship('client', 'company_name')
                        ->searchable()->preload()->required()->live(),
                    Forms\Components\Select::make('contact_id')
                        ->label('Contact')
                        ->relationship('contact', 'full_name', fn (Forms\Get $get, $query) => $get('client_id')
                            ? $query->where('client_id', $get('client_id'))
                            : $query)
                        ->searchable()->preload(),
                    Forms\Components\Select::make('opportunity_id')
                        ->label('Opportunity')
                        ->relationship('opportunity', 'opportunity_number')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('currency')
                        ->options(['KES' => 'KES', 'USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP'])
                        ->default($currency)->required(),
                    Forms\Components\DatePicker::make('validity_date'),
                    Forms\Components\TextInput::make('freight')->numeric()->prefix($currency)->default(0),
                    Forms\Components\Textarea::make('terms')->columnSpanFull(),
                ])->columns(3),
                Forms\Components\Section::make('Line Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('material_id')
                                    ->label('Product')
                                    ->relationship('material', 'name')
                                    ->searchable()->preload(),
                                Forms\Components\TextInput::make('description'),
                                Forms\Components\TextInput::make('quantity')->numeric()->required(),
                                Forms\Components\TextInput::make('unit_price')->numeric()->prefix($currency)->required(),
                                Forms\Components\TextInput::make('discount_percent')->numeric()->suffix('%')->default(0),
                                Forms\Components\TextInput::make('tax_percent')->numeric()->suffix('%')->default(0),
                            ])
                            ->columns(3)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Add Line')
                            ->reorderable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')->label('Quotation #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('total')->money($currency)->sortable(),
                Tables\Columns\TextColumn::make('validity_date')->date()->sortable()
                    ->color(fn (Quotation $record) => $record->isExpired() ? 'danger' : null)
                    ->description(fn (Quotation $record) => $record->isExpired() ? 'Expired' : null),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Approved' => 'Approved',
                    'Accepted' => 'Accepted', 'Rejected' => 'Rejected', 'Expired' => 'Expired',
                ]),
                Tables\Filters\SelectFilter::make('client_id')->relationship('client', 'company_name'),
            ])
            ->actions([
                Tables\Actions\Action::make('submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Quotation $record) => $record->status === 'Draft')
                    ->requiresConfirmation()
                    ->action(fn (Quotation $record) => $record->update(['status' => 'Submitted'])),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Quotation $record) => $record->status === 'Submitted'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('crm.approve')))
                    ->requiresConfirmation()
                    ->action(fn (Quotation $record) => $record->update(['status' => 'Approved'])),
                Tables\Actions\Action::make('accept')
                    ->label('Accept & Create Order')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->visible(fn (Quotation $record) => in_array($record->status, ['Submitted', 'Approved'], true))
                    ->requiresConfirmation()
                    ->modalDescription('This creates a Customer Order for each line item.')
                    ->action(function (Quotation $record) {
                        try {
                            $orders = $record->accept();
                            $numbers = collect($orders)->pluck('order_number')->implode(', ');
                            Notification::make()->title("Quotation accepted — order(s) created: {$numbers}")->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title('Cannot accept quotation')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Quotation $record) => in_array($record->status, ['Submitted', 'Approved'], true))
                    ->requiresConfirmation()
                    ->action(fn (Quotation $record) => $record->update(['status' => 'Rejected'])),
                Tables\Actions\EditAction::make()->visible(fn (Quotation $record) => $record->status === 'Draft'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}
